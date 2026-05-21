@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block; text-align: center;">
<img src="{{ rtrim(config('app.url'), '/') }}/assets/img/logo.png" class="logo" alt="{{ trim($slot) }}" style="display: block; margin: 0 auto 10px;">
<span style="display: block;">{!! $slot !!}</span>
</a>
</td>
</tr>
