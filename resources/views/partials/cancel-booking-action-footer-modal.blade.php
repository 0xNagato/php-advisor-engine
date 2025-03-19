@if($canModifyBooking)
    <p class="text-center p-3 space-y-1 mb-2 text-xs font-medium text-blue-600 bg-blue-50 border border-blue-200 rounded-md">
        If you need to change the date of this booking, <br>
        please use Modify this booking instead of cancelling.
    </p>
@endif
<div class="flex flex-row justify-start space-x-4 sm:space-x-6 md:space-x-8">
    @if($canModifyBooking)
        {{ $action->getModalAction('modifyBooking') }}
    @endif
    {{ $action->getModalAction('cancelThisBooking') }}
</div>
