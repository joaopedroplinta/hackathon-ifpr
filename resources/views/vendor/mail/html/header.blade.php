@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block; text-decoration: none;">
<table role="presentation" cellpadding="0" cellspacing="0" align="left">
<tr>
<td class="logo-badge">&gt;_</td>
<td class="logo-text">{!! $slot !!}</td>
</tr>
</table>
</a>
</td>
</tr>
