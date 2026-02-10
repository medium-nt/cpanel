<hr class="d-block d-md-none">
<div class="row">
    <div class="col-md-9">
        <div class="form-group">
            <label for="item_id{{ $i }}" class="d-block d-md-none">Товар</label>
            <select name="item_id[]"
                    id="item_id{{ $i }}"
                    class="form-control item_id">
                <option value="" disabled selected>---</option>
                @foreach($items as $item)
                    <option value="{{ $item->id }}"
                            @if(old('item_id.'.$i) == $item->id) selected @endif>
                        {{ $item->title }} {{ $item->width }}х{{ $item->height }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="col-md-3">
        <div class="form-group">
            <label for="quantity{{ $i }}"
                   class="d-block d-md-none">Количество</label>
            <input type="number"
                   class="form-control @error('quantity.'.$i) is-invalid @enderror"
                   id="quantity{{ $i }}"
                   name="quantity[]"
                   step="1"
                   min="1"
                   disabled
                   @if(old('quantity.'.$i)) value="{{ old('quantity.'.$i) }}" @endif
            >
        </div>
    </div>
</div>
