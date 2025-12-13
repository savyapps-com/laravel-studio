<?php

namespace SavyApps\LaravelStudio\Tests\Unit;

use SavyApps\LaravelStudio\Resources\Fields\Media;
use SavyApps\LaravelStudio\Tests\TestCase;

class MediaFieldSecurityTest extends TestCase
{
    /** @test */
    public function it_blocks_php_files(): void
    {
        $field = Media::make('avatar');

        $this->assertFalse($field->isExtensionAllowed('malicious.php'));
        $this->assertFalse($field->isExtensionAllowed('malicious.phtml'));
        $this->assertFalse($field->isExtensionAllowed('malicious.php5'));
        $this->assertFalse($field->isExtensionAllowed('malicious.phar'));
    }

    /** @test */
    public function it_blocks_executable_files(): void
    {
        $field = Media::make('file');

        $this->assertFalse($field->isExtensionAllowed('virus.exe'));
        $this->assertFalse($field->isExtensionAllowed('script.bat'));
        $this->assertFalse($field->isExtensionAllowed('script.cmd'));
        $this->assertFalse($field->isExtensionAllowed('trojan.msi'));
        $this->assertFalse($field->isExtensionAllowed('malware.dll'));
    }

    /** @test */
    public function it_blocks_script_files(): void
    {
        $field = Media::make('file');

        $this->assertFalse($field->isExtensionAllowed('script.js'));
        $this->assertFalse($field->isExtensionAllowed('script.vbs'));
        $this->assertFalse($field->isExtensionAllowed('script.sh'));
        $this->assertFalse($field->isExtensionAllowed('script.py'));
        $this->assertFalse($field->isExtensionAllowed('script.pl'));
    }

    /** @test */
    public function it_blocks_server_side_scripts(): void
    {
        $field = Media::make('file');

        $this->assertFalse($field->isExtensionAllowed('page.asp'));
        $this->assertFalse($field->isExtensionAllowed('page.aspx'));
        $this->assertFalse($field->isExtensionAllowed('page.jsp'));
        $this->assertFalse($field->isExtensionAllowed('page.cfm'));
    }

    /** @test */
    public function it_blocks_config_files(): void
    {
        $field = Media::make('file');

        $this->assertFalse($field->isExtensionAllowed('.htaccess'));
        $this->assertFalse($field->isExtensionAllowed('.htpasswd'));
        $this->assertFalse($field->isExtensionAllowed('web.config'));
    }

    /** @test */
    public function it_blocks_svg_by_default_in_images_method(): void
    {
        $field = Media::make('avatar')->images();

        // SVG is not in allowed extensions for images()
        $this->assertFalse($field->isExtensionAllowed('logo.svg'));
    }

    /** @test */
    public function it_allows_svg_with_explicit_method(): void
    {
        $field = Media::make('logo')->imagesWithSvg();

        $this->assertTrue($field->isExtensionAllowed('logo.svg'));
        $this->assertTrue($field->isExtensionAllowed('icon.jpg'));
    }

    /** @test */
    public function it_allows_safe_image_extensions(): void
    {
        $field = Media::make('avatar')->images();

        $this->assertTrue($field->isExtensionAllowed('photo.jpg'));
        $this->assertTrue($field->isExtensionAllowed('photo.jpeg'));
        $this->assertTrue($field->isExtensionAllowed('image.png'));
        $this->assertTrue($field->isExtensionAllowed('animation.gif'));
        $this->assertTrue($field->isExtensionAllowed('modern.webp'));
    }

    /** @test */
    public function it_allows_document_extensions(): void
    {
        $field = Media::make('document')->documents();

        $this->assertTrue($field->isExtensionAllowed('report.pdf'));
        $this->assertTrue($field->isExtensionAllowed('letter.doc'));
        $this->assertTrue($field->isExtensionAllowed('proposal.docx'));
    }

    /** @test */
    public function it_filters_dangerous_extensions_from_custom_whitelist(): void
    {
        $field = Media::make('file')->allowedExtensions(['jpg', 'php', 'png', 'exe']);

        // Should allow safe ones
        $this->assertTrue($field->isExtensionAllowed('image.jpg'));
        $this->assertTrue($field->isExtensionAllowed('image.png'));

        // Should block dangerous ones even if explicitly added
        $this->assertFalse($field->isExtensionAllowed('malicious.php'));
        $this->assertFalse($field->isExtensionAllowed('virus.exe'));
    }

    /** @test */
    public function it_handles_case_insensitive_extensions(): void
    {
        $field = Media::make('avatar')->images();

        $this->assertTrue($field->isExtensionAllowed('photo.JPG'));
        $this->assertTrue($field->isExtensionAllowed('photo.JPEG'));
        $this->assertTrue($field->isExtensionAllowed('photo.Png'));
    }

    /** @test */
    public function it_blocks_double_extensions(): void
    {
        $field = Media::make('file')->images();

        // File with double extension - pathinfo gets the last one
        $this->assertFalse($field->isExtensionAllowed('image.jpg.php'));
        $this->assertFalse($field->isExtensionAllowed('document.pdf.exe'));
    }

    /** @test */
    public function it_includes_allowed_extensions_in_array_output(): void
    {
        $field = Media::make('avatar')->images();
        $array = $field->toArray();

        $this->assertArrayHasKey('allowedExtensions', $array);
        $this->assertContains('jpg', $array['allowedExtensions']);
        $this->assertContains('png', $array['allowedExtensions']);
        $this->assertNotContains('svg', $array['allowedExtensions']);
    }

    /** @test */
    public function it_returns_dangerous_extensions_list(): void
    {
        $dangerous = Media::getDangerousExtensions();

        $this->assertContains('php', $dangerous);
        $this->assertContains('exe', $dangerous);
        $this->assertContains('js', $dangerous);
        $this->assertContains('svg', $dangerous);
    }

    /** @test */
    public function it_allows_any_safe_extension_when_no_whitelist(): void
    {
        $field = Media::make('file'); // No whitelist set

        // Safe extensions should be allowed
        $this->assertTrue($field->isExtensionAllowed('file.txt'));
        $this->assertTrue($field->isExtensionAllowed('file.csv'));
        $this->assertTrue($field->isExtensionAllowed('file.zip'));

        // Dangerous extensions should still be blocked
        $this->assertFalse($field->isExtensionAllowed('file.php'));
        $this->assertFalse($field->isExtensionAllowed('file.exe'));
    }
}
