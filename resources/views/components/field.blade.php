@props(['name', 'label', 'type' => 'text', 'value' => null, 'hint' => null, 'required' => false, 'options' => null, 'placeholder' => null])
@php $id = 'f_'.str_replace(['[', ']', '.'], '_', $name); $err = $errors->first($name); @endphp
<div {{ $attributes->only('class')->merge(['class' => 'field']) }}>
    <label for="{{ $id }}">{{ $label }}@if ($required) <span aria-hidden="true" style="color:var(--danger)">*</span>@endif</label>
    @if ($type === 'select')
        <select id="{{ $id }}" name="{{ $name }}" class="select {{ $err ? 'is-invalid' : '' }}" @required($required) {{ $attributes->except('class') }}>
            {{ $slot }}
        </select>
    @elseif ($type === 'textarea')
        <textarea id="{{ $id }}" name="{{ $name }}" class="textarea {{ $err ? 'is-invalid' : '' }}" @required($required) placeholder="{{ $placeholder }}" {{ $attributes->except('class') }}>{{ old($name, $value) }}</textarea>
    @else
        <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}" value="{{ $type === 'password' ? '' : old($name, $value) }}" class="input {{ $err ? 'is-invalid' : '' }}" @required($required) placeholder="{{ $placeholder }}" {{ $attributes->except('class') }} @if($err) aria-invalid="true" aria-describedby="{{ $id }}_err" @endif>
    @endif
    @if ($hint)<span class="hint">{{ $hint }}</span>@endif
    @if ($err)<span class="error" id="{{ $id }}_err">{{ $err }}</span>@endif
</div>
