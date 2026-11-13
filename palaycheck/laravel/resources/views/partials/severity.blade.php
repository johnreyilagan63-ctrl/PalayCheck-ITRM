@php($level = in_array($severity, ['low', 'medium', 'high'], true) ? $severity : 'low')
<span class="badge sev-{{ $level }}">{{ strtoupper($level) }}</span>
