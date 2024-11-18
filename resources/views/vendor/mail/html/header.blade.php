@props(['url'])
<tr>
    <td class="header">
        <a href="{{ $url }}" style="display: inline-block;">
            <img src="{{ config('app.url') }}/assets/images/email-logo.png" class="logo" alt="PRIMA Logo" width="200"
                height="50" style="width: 200px; height: 50px; object-fit: contain;">
        </a>
    </td>
</tr>
