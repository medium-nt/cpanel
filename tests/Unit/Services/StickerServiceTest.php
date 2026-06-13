<?php

namespace Tests\Unit\Services;

use App\Services\StickerService;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

class StickerServiceTest extends PHPUnitTestCase
{
    /**
     * Test resolveTemplate method with special sticker title for OZON marketplace.
     */
    public function test_resolve_template_with_special_sticker_title_for_ozon(): void
    {
        // Arrange
        $itemTitle = 'ВУАЛЬ (БЕЗ УТ)';
        $marketplaceId = 1; // OZON

        // Act
        $template = StickerService::resolveTemplate($itemTitle, $marketplaceId);

        // Assert
        $this->assertEquals('pdf.fbo_sticker', $template);
    }

    /**
     * Test resolveTemplate method with special sticker title for Wildberries marketplace.
     */
    public function test_resolve_template_with_special_sticker_title_for_wildberries(): void
    {
        // Arrange
        $itemTitle = 'ВУАЛЬ (БЕЗ УТ)';
        $marketplaceId = 2; // Wildberries

        // Act
        $template = StickerService::resolveTemplate($itemTitle, $marketplaceId);

        // Assert
        $this->assertEquals('pdf.fbo_sticker', $template);
    }

    /**
     * Test resolveTemplate method with case-insensitive matching for special sticker.
     */
    public function test_resolve_template_case_insensitive_special_sticker(): void
    {
        // Arrange
        $itemTitle = 'вуаль (без ут)';
        $marketplaceId = 1;

        // Act
        $template = StickerService::resolveTemplate($itemTitle, $marketplaceId);

        // Assert
        $this->assertEquals('pdf.fbo_sticker', $template);
    }

    /**
     * Test resolveTemplate method with partial match for special sticker.
     */
    public function test_resolve_template_partial_match_special_sticker(): void
    {
        // Arrange
        $itemTitle = 'ТОВАР ВУАЛЬ (БЕЗ УТ) РАЗМЕР';
        $marketplaceId = 1;

        // Act
        $template = StickerService::resolveTemplate($itemTitle, $marketplaceId);

        // Assert
        $this->assertEquals('pdf.fbo_sticker', $template);
    }

    /**
     * Test resolveTemplate method with OZON marketplace without special sticker.
     */
    public function test_resolve_template_ozon_marketplace_regular_item(): void
    {
        // Arrange
        $itemTitle = 'Обычный товар OZON';
        $marketplaceId = 1; // OZON

        // Act
        $template = StickerService::resolveTemplate($itemTitle, $marketplaceId);

        // Assert
        $this->assertEquals('pdf.fbo_ozon_sticker', $template);
    }

    /**
     * Test resolveTemplate method with Wildberries marketplace without special sticker.
     */
    public function test_resolve_template_wildberries_marketplace_regular_item(): void
    {
        // Arrange
        $itemTitle = 'Обычный товар Wildberries';
        $marketplaceId = 2; // Wildberries

        // Act
        $template = StickerService::resolveTemplate($itemTitle, $marketplaceId);

        // Assert
        $this->assertEquals('pdf.fbo_wb_sticker', $template);
    }

    /**
     * Test resolveTemplate method with unknown marketplace ID defaults to Wildberries template.
     */
    public function test_resolve_template_unknown_marketplace_id(): void
    {
        // Arrange
        $itemTitle = 'Обычный товар';
        $marketplaceId = 99; // Unknown

        // Act
        $template = StickerService::resolveTemplate($itemTitle, $marketplaceId);

        // Assert
        $this->assertEquals('pdf.fbo_wb_sticker', $template);
    }

    /**
     * Test resolveFontSizeCluster method with FBO sticker template - long text.
     */
    public function test_resolve_font_size_cluster_fbo_long_text(): void
    {
        // Arrange
        $cluster = str_repeat('А', 26); // 26 characters
        $template = 'pdf.fbo_sticker';

        // Act
        $fontSize = StickerService::resolveFontSizeCluster($cluster, $template);

        // Assert
        $this->assertEquals(6, $fontSize);
    }

    /**
     * Test resolveFontSizeCluster method with FBO sticker template - medium text.
     */
    public function test_resolve_font_size_cluster_fbo_medium_text(): void
    {
        // Arrange
        $cluster = str_repeat('А', 20); // 20 characters
        $template = 'pdf.fbo_sticker';

        // Act
        $fontSize = StickerService::resolveFontSizeCluster($cluster, $template);

        // Assert
        $this->assertEquals(10, $fontSize);
    }

    /**
     * Test resolveFontSizeCluster method with FBO sticker template - short text.
     */
    public function test_resolve_font_size_cluster_fbo_short_text(): void
    {
        // Arrange
        $cluster = str_repeat('А', 10); // 10 characters
        $template = 'pdf.fbo_sticker';

        // Act
        $fontSize = StickerService::resolveFontSizeCluster($cluster, $template);

        // Assert
        $this->assertEquals(14, $fontSize);
    }

    /**
     * Test resolveFontSizeCluster method with OZON sticker template - long text.
     */
    public function test_resolve_font_size_cluster_ozon_long_text(): void
    {
        // Arrange
        $cluster = str_repeat('Б', 26); // 26 characters
        $template = 'pdf.fbo_ozon_sticker';

        // Act
        $fontSize = StickerService::resolveFontSizeCluster($cluster, $template);

        // Assert
        $this->assertEquals(7, $fontSize);
    }

    /**
     * Test resolveFontSizeCluster method with OZON sticker template - medium text.
     */
    public function test_resolve_font_size_cluster_ozon_medium_text(): void
    {
        // Arrange
        $cluster = str_repeat('Б', 20); // 20 characters
        $template = 'pdf.fbo_ozon_sticker';

        // Act
        $fontSize = StickerService::resolveFontSizeCluster($cluster, $template);

        // Assert
        $this->assertEquals(11, $fontSize);
    }

    /**
     * Test resolveFontSizeCluster method with OZON sticker template - short text.
     */
    public function test_resolve_font_size_cluster_ozon_short_text(): void
    {
        // Arrange
        $cluster = str_repeat('Б', 15); // 15 characters
        $template = 'pdf.fbo_ozon_sticker';

        // Act
        $fontSize = StickerService::resolveFontSizeCluster($cluster, $template);

        // Assert
        $this->assertEquals(14, $fontSize);
    }

    /**
     * Test resolveFontSizeCluster method with Wildberries sticker template - long text.
     */
    public function test_resolve_font_size_cluster_wildberries_long_text(): void
    {
        // Arrange
        $cluster = str_repeat('В', 26); // 26 characters
        $template = 'pdf.fbo_wb_sticker';

        // Act
        $fontSize = StickerService::resolveFontSizeCluster($cluster, $template);

        // Assert
        $this->assertEquals(4, $fontSize);
    }

    /**
     * Test resolveFontSizeCluster method with Wildberries sticker template - medium text.
     */
    public function test_resolve_font_size_cluster_wildberries_medium_text(): void
    {
        // Arrange
        $cluster = str_repeat('В', 20); // 20 characters
        $template = 'pdf.fbo_wb_sticker';

        // Act
        $fontSize = StickerService::resolveFontSizeCluster($cluster, $template);

        // Assert
        $this->assertEquals(7, $fontSize);
    }

    /**
     * Test resolveFontSizeCluster method with Wildberries sticker template - short text.
     */
    public function test_resolve_font_size_cluster_wildberries_short_text(): void
    {
        // Arrange
        $cluster = str_repeat('В', 10); // 10 characters
        $template = 'pdf.fbo_wb_sticker';

        // Act
        $fontSize = StickerService::resolveFontSizeCluster($cluster, $template);

        // Assert
        $this->assertEquals(10, $fontSize);
    }

    /**
     * Test resolveFontSizeCluster method with null cluster parameter.
     */
    public function test_resolve_font_size_cluster_null_cluster(): void
    {
        // Arrange
        $cluster = null;
        $template = 'pdf.fbo_sticker';

        // Act
        $fontSize = StickerService::resolveFontSizeCluster($cluster, $template);

        // Assert
        $this->assertEquals(14, $fontSize);
    }

    /**
     * Test resolveFontSizeCluster method with empty string cluster parameter.
     */
    public function test_resolve_font_size_cluster_empty_cluster(): void
    {
        // Arrange
        $cluster = '';
        $template = 'pdf.fbo_sticker';

        // Act
        $fontSize = StickerService::resolveFontSizeCluster($cluster, $template);

        // Assert
        $this->assertEquals(14, $fontSize);
    }

    /**
     * Test resolveFontSizeCluster method with unknown template defaults to 10.
     */
    public function test_resolve_font_size_cluster_unknown_template(): void
    {
        // Arrange
        $cluster = 'тест';
        $template = 'unknown.template';

        // Act
        $fontSize = StickerService::resolveFontSizeCluster($cluster, $template);

        // Assert
        $this->assertEquals(10, $fontSize);
    }

    /**
     * Test integration: template selection affects font size calculation.
     */
    public function test_template_and_font_size_integration(): void
    {
        // Arrange
        $itemTitle = 'ВУАЛЬ (БЕЗ УТ)';
        $marketplaceId = 1;
        $cluster = str_repeat('Т', 20); // Medium length text

        // Act
        $template = StickerService::resolveTemplate($itemTitle, $marketplaceId);
        $fontSize = StickerService::resolveFontSizeCluster($cluster, $template);

        // Assert
        $this->assertEquals('pdf.fbo_sticker', $template);
        $this->assertEquals(10, $fontSize);
    }

    /**
     * Test special boundary case: exactly 25 characters for FBO sticker.
     */
    public function test_resolve_font_size_cluster_fbo_boundary_25_chars(): void
    {
        // Arrange
        $cluster = str_repeat('А', 25); // Exactly 25 characters
        $template = 'pdf.fbo_sticker';

        // Act
        $fontSize = StickerService::resolveFontSizeCluster($cluster, $template);

        // Assert
        $this->assertEquals(10, $fontSize);
    }

    /**
     * Test special boundary case: exactly 18 characters for FBO sticker.
     */
    public function test_resolve_font_size_cluster_fbo_boundary_18_chars(): void
    {
        // Arrange
        $cluster = str_repeat('А', 18); // Exactly 18 characters
        $template = 'pdf.fbo_sticker';

        // Act
        $fontSize = StickerService::resolveFontSizeCluster($cluster, $template);

        // Assert
        $this->assertEquals(14, $fontSize);
    }

    /**
     * Test special boundary case: exactly 25 characters for OZON sticker.
     */
    public function test_resolve_font_size_cluster_ozon_boundary_25_chars(): void
    {
        // Arrange
        $cluster = str_repeat('Б', 25); // Exactly 25 characters
        $template = 'pdf.fbo_ozon_sticker';

        // Act
        $fontSize = StickerService::resolveFontSizeCluster($cluster, $template);

        // Assert
        $this->assertEquals(11, $fontSize);
    }

    /**
     * Test special boundary case: exactly 18 characters for Wildberries sticker.
     */
    public function test_resolve_font_size_cluster_wildberries_boundary_18_chars(): void
    {
        // Arrange
        $cluster = str_repeat('В', 18); // Exactly 18 characters
        $template = 'pdf.fbo_wb_sticker';

        // Act
        $fontSize = StickerService::resolveFontSizeCluster($cluster, $template);

        // Assert
        $this->assertEquals(10, $fontSize);
    }

    /**
     * Test resolveTemplate method with exact special sticker title match.
     */
    public function test_resolve_template_exact_special_sticker_match(): void
    {
        // Arrange
        $itemTitle = 'ВУАЛЬ (БЕЗ УТ)';
        $marketplaceId = 1;

        // Act
        $template = StickerService::resolveTemplate($itemTitle, $marketplaceId);

        // Assert
        $this->assertEquals('pdf.fbo_sticker', $template);
    }

    /**
     * Test resolveTemplate method with special sticker at beginning of title.
     */
    public function test_resolve_template_special_sticker_at_beginning(): void
    {
        // Arrange
        $itemTitle = 'ВУАЛЬ (БЕЗ УТ) Начало';
        $marketplaceId = 2;

        // Act
        $template = StickerService::resolveTemplate($itemTitle, $marketplaceId);

        // Assert
        $this->assertEquals('pdf.fbo_sticker', $template);
    }

    /**
     * Test resolveTemplate method with special sticker at end of title.
     */
    public function test_resolve_template_special_sticker_at_end(): void
    {
        // Arrange
        $itemTitle = 'Конец ВУАЛЬ (БЕЗ УТ)';
        $marketplaceId = 1;

        // Act
        $template = StickerService::resolveTemplate($itemTitle, $marketplaceId);

        // Assert
        $this->assertEquals('pdf.fbo_sticker', $template);
    }

    /**
     * Test resolveTemplate method with marketplace ID 0 (edge case).
     */
    public function test_resolve_template_marketplace_id_zero(): void
    {
        // Arrange
        $itemTitle = 'Обычный товар';
        $marketplaceId = 0;

        // Act
        $template = StickerService::resolveTemplate($itemTitle, $marketplaceId);

        // Assert
        $this->assertEquals('pdf.fbo_wb_sticker', $template);
    }

    /**
     * Test resolveFontSizeCluster method with special characters in cluster.
     */
    public function test_resolve_font_size_cluster_special_characters(): void
    {
        // Arrange
        $cluster = 'тест@#$%^&*()';
        $template = 'pdf.fbo_sticker';

        // Act
        $fontSize = StickerService::resolveFontSizeCluster($cluster, $template);

        // Assert
        $this->assertEquals(14, $fontSize); // Should be treated as short text
    }

    /**
     * Test resolveFontSizeCluster method with spaces in cluster.
     */
    public function test_resolve_font_size_cluster_with_spaces(): void
    {
        // Arrange
        $cluster = 'тест с пробелами';
        $template = 'pdf.fbo_ozon_sticker';

        // Act
        $fontSize = StickerService::resolveFontSizeCluster($cluster, $template);

        // Assert
        $this->assertEquals(14, $fontSize); // 13 chars with spaces, should be short
    }

    /**
     * Test resolveFontSizeCluster method with numbers in cluster.
     */
    public function test_resolve_font_size_cluster_with_numbers(): void
    {
        // Arrange
        $cluster = '123456789012345678901';
        $template = 'pdf.fbo_wb_sticker';

        // Act
        $fontSize = StickerService::resolveFontSizeCluster($cluster, $template);

        // Assert
        $this->assertEquals(7, $fontSize); // 21 chars, should be medium
    }
}
