@props(['url'])
<tr>
<td class="header-wrapper" align="center">
<table class="header" align="center" width="600" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td class="header-content">
<table role="presentation" cellpadding="0" cellspacing="0">
<tr>
<td class="logo-badge">
<a href="{{ $url }}" class="logo-link" aria-label="Acessar {{ config('app.name') }}"><span class="logo-mark" aria-hidden="true">&#9632;</span></a>
</td>
<td class="logo-copy">
<a href="{{ $url }}" class="brand-link">
<span class="logo-text">{!! $slot !!}</span>
<span class="logo-subtitle">IFPR Campus Pinhais</span>
</a>
</td>
</tr>
</table>
</td>
</tr>
</table>
</td>
</tr>
