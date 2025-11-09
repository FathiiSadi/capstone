@props(['name', 'label'])

<div class="inline-flex items-center gap-x-2">
    <span class="w-2 h-2 bg-black inline-block"></span>
    <label class="form-label" for="{{ $name }}">{{ $label }}</label>
</div>
