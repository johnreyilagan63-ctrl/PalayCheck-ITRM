@php($max = collect($rows)->max('value') ?: 0)
@forelse ($rows as $row)
  <div class="d-flex align-items-center gap-2 small mb-1"><span style="width:90px">{{ $row['label'] }}</span>
    <div class="bar flex-grow-1"><i style="width:{{ $max ? round($row['value'] / $max * 100, 1) : 0 }}%"></i></div><b>{{ $row['value'] }}</b></div>
@empty
  <span class="text-muted small">No data available yet.</span>
@endforelse
