@php($img = pest_image($pest->name))
<div class="col-md-6 col-lg-3"><div class="card pest-card h-100">
  <div class="pest-thumb">@if ($img)<img src="{{ $img }}" alt="{{ $pest->name }}" loading="lazy">@else<i class="bi bi-bug"></i>@endif</div>
  <div class="card-body d-flex flex-column"><h5 class="mb-0">{{ $pest->name }}</h5><em class="text-muted small">{{ $pest->scientific_name }}</em>
    <p class="mt-2 small flex-grow-1">{{ \Illuminate\Support\Str::limit((string) $pest->description, 110) }}</p>
    <a class="btn btn-sm btn-outline-pc align-self-start" href="{{ route('pests.show', $pest) }}">View Details</a></div></div></div>
