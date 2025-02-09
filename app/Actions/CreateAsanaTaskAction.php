<?php

namespace App\Actions;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Action;

class CreateAsanaTaskAction extends Action
{
    /**
     * Create a new task in Asana using the provided task data.
     *
     * @param  array  $taskData  Array containing keys "name" and "notes"
     */
    public function handle(array $taskData): array
    {
        Log::info('Starting Asana task creation', ['task_data' => $taskData]);

        $asanaToken = config('services.asana.token');
        $workspaceId = config('services.asana.workspace_id'); // 1209142958028718
        $projectId = config('services.asana.project_id');     // 1209152660457936

        if (! $asanaToken || ! $workspaceId || ! $projectId) {
            Log::error('Missing required Asana configuration', [
                'has_token' => (bool) $asanaToken,
                'has_workspace_id' => (bool) $workspaceId,
                'has_project_id' => (bool) $projectId,
            ]);

            return [
                'success' => false,
                'task_url' => null,
            ];
        }

        $payload = [
            'data' => [
                'name' => $taskData['name'] ?? 'New Task',
                'notes' => $taskData['notes'] ?? '',
                'assignee' => 'me',
                'projects' => [$projectId],
                'workspace' => $workspaceId,
            ],
        ];

        Log::debug('Sending request to Asana API', [
            'payload' => $payload,
            'workspace_id' => $workspaceId,
            'project_id' => $projectId,
        ]);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$asanaToken,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->post('https://app.asana.com/api/1.0/tasks', $payload);

        if ($response->successful()) {
            $taskId = $response->json('data.gid');
            $taskUrl = "https://app.asana.com/0/{$projectId}/{$taskId}";

            Log::info('Successfully created Asana task', [
                'status' => $response->status(),
                'task_id' => $taskId,
                'task_url' => $taskUrl,
            ]);

            return [
                'success' => true,
                'task_url' => $taskUrl,
            ];
        }

        Log::error('Failed to create Asana task', [
            'status' => $response->status(),
            'body' => $response->body(),
            'payload' => $payload,
            'workspace_id' => $workspaceId,
            'project_id' => $projectId,
        ]);

        return [
            'success' => false,
            'task_url' => null,
        ];
    }
}
