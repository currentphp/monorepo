@php($error = $errors->first($name))
<div class="field">
    <label for="field-{{ $name }}">{{ $label }}</label>
    <input id="field-{{ $name }}" name="{{ $name }}" type="{{ $type ?? 'text' }}" value="{{ $value }}" required maxlength="{{ $maxLength }}" @isset($autocomplete) autocomplete="{{ $autocomplete }}" @endisset @if($error) aria-invalid="true" aria-describedby="field-{{ $name }}-error" @endif @if($live ?? false) wire:model="{{ $name }}" @endif>
    @if($error) <p id="field-{{ $name }}-error" class="error">{{ $error }}</p> @endif
</div>
