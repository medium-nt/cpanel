<hr class="d-block d-md-none">
<div class="row">
    <div class="col-md-10 form-group">
        <label for="material_id" class="d-block d-md-none">Материал</label>
        <select name="material_id[]" id="material_id" class="form-control">
            <option value="" disabled selected>---</option>
            @foreach($materials as $material)
                <option value="{{ $material->id }}">{{ $material->title }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-2 form-group">
        <label for="quantity" class="d-block d-md-none">Кол-во</label>
        <input type="number"
               class="form-control @error('amount') is-invalid @enderror"
               id="quantity"
               name="quantity[]"
               step="0.01"
               min="0.01">
    </div>
</div>
