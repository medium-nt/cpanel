<div class="row">
    <div class="col-md-10 form-group">
        <select name="material_id[]" id="material_id" class="form-control">
            <option value="" disabled selected>---</option>
            @foreach($materials as $material)
                <option value="{{ $material->id }}">{{ $material->title }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-2 form-group">
        <input type="number"
               class="form-control @error('amount') is-invalid @enderror"
               id="quantity"
               name="quantity[]"
               step="1"
               value="{{ old('amount') }}">
    </div>
</div>
