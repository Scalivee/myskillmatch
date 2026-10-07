@props(['name','label','type'=>'text','value'=>'','required'=>false])
@php($oldKey=trim(str_replace(['[',']'],['.',''],$name),'.'))
@php($id=str_replace(['[',']','.'],['_','','_'],$name))
<div class="field"><label for="{{ $id }}">{{ $label }} @if($required)<span aria-hidden="true">*</span>@endif</label><input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}" value="{{ $type==='password'?'':old($oldKey,$value) }}" @required($required) {{ $attributes }} @error($oldKey) aria-invalid="true" aria-describedby="{{ $id }}_error" @enderror>@error($oldKey)<p class="error" id="{{ $id }}_error">{{ $message }}</p>@enderror</div>
