<hr class="d-block d-md-none">
<div class="row">
    <div class="col-md-9">
        <div class="form-group">
            <label for="item_id" class="d-block d-md-none">Товар</label>
            <select name="item_id[]"
                    id="item_id{{$i}}"
                    class="form-control item_id">
                <option value="" disabled selected>---</option>
                @foreach($items as $item)
                    <option value="{{ $item->id }}">
                        {{ $item->title }} {{ $item->width }}х{{ $item->height }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="col-md-3">
        <div class="form-group">
            <label for="quantity" class="d-block d-md-none">Количество</label>
            <input type="number"
                   class="form-control @error('quantity') is-invalid @enderror"
                   id="quantity"
                   name="quantity[]"
                   step="1"
                   disabled
            >
        </div>
    </div>
</div>
