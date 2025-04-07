<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <select name="item_id[]"
                    id="item_id"
                    class="form-control">
                <option value="" disabled selected>---</option>
                @foreach($items as $item)
                    <option value="{{ $item->id }}">{{ $item->title }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="col-md-3">
        <div class="form-group">
            <input type="number"
                   class="form-control @error('quantity') is-invalid @enderror"
                   id="quantity"
                   name="quantity[]"
                   step="1"
            >
        </div>
    </div>

    <div class="col-md-3">
        <div class="form-group">
            <input type="number"
                   class="form-control @error('price') is-invalid @enderror"
                   id="price"
                   name="price[]"
            >
        </div>
    </div>
</div>
