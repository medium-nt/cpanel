<div class="row">
    <div class="col-md-10 form-group">
        <label for="material_id">Материал</label>
        <select wire:model.live="selectedMaterialId" id="material_id"
                name="material_id[]" class="form-control">
            <option value="" disabled selected>---</option>
            @foreach($materials as $material)
                <option value="{{ $material->id }}">{{ $material->title }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-2 form-group">
        <label for="ordered_quantity">Количество</label>
        <input wire:model="orderedQuantity" type="number"
               class="form-control @error('orderedQuantity') is-invalid @enderror"
               id="ordered_quantity"
               name="ordered_quantity[]"
               step="0.01"
               value="{{ old('orderedQuantity') }}"
               min = 0.01
               max = {{ $maxQuantity }}>
        <span class="invalid-feedback d-block mt-0" id="max">max = {{ $maxQuantity }}</span>
        @error('orderedQuantity')
            <span class="invalid-feedback d-block mt-0">{{ $message }}</span>
        @enderror
    </div>
</div>
