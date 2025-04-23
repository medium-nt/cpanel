<div style="text-align: center; font-size: 18px;">
    <div class="btn-group" role="group">
        {{ $collection->onEachSide(1)->links() }}
    </div>
</div>

<style>
    @media (max-width: 600px) {
        .page-link {
            padding: .5rem .5rem;
        }
    }
</style>
