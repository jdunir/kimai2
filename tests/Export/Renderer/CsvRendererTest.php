<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export\Renderer;

use App\Export\Renderer\CsvRenderer;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * @covers \App\Export\Renderer\CsvRenderer
 * @covers \App\Export\Renderer\AbstractSpreadsheetRenderer
 * @covers \App\Export\Renderer\RendererTrait
 * @group integration
 */
class CsvRendererTest extends AbstractRendererTest
{
    public function testConfiguration()
    {
        $sut = $this->getAbstractRenderer(CsvRenderer::class);

        $this->assertEquals('csv', $sut->getId());
        $this->assertEquals('csv', $sut->getTitle());
        $this->assertEquals('csv', $sut->getIcon());
    }

    public function getTestModel()
    {
        return [
            ['01:50 h', '€2,437.12', '€1,947.99', 7, 5, 1, 2, 2]
        ];
    }

    /**
     * @dataProvider getTestModel
     */
    public function testRender($totalDuration, $totalRate, $expectedRate, $expectedRows, $expectedDescriptions, $expectedUser1, $expectedUser2, $expectedUser3)
    {
        $sut = $this->getAbstractRenderer(CsvRenderer::class);

        /** @var BinaryFileResponse $response */
        $response = $this->render($sut);

        $file = $response->getFile();
        $this->assertEquals('text/csv', $response->headers->get('Content-Type'));
        $this->assertEquals('attachment; filename=kimai-export.csv', $response->headers->get('Content-Disposition'));

        $this->assertTrue(file_exists($file->getRealPath()));
        $content = file_get_contents($file->getRealPath());

        $this->assertContains('"' . $totalDuration . '"', $content);
        $this->assertContains('"' . $totalRate . '"', $content);
        $this->assertContains('"' . $expectedRate . '"', $content);
        $this->assertEquals($expectedRows, substr_count($content, PHP_EOL));
        $this->assertEquals($expectedDescriptions, substr_count($content, 'activity description'));
        $this->assertEquals($expectedUser1, substr_count($content, ',"kevin",'));
        $this->assertEquals($expectedUser3, substr_count($content, ',"hello-world",'));
        $this->assertEquals($expectedUser2, substr_count($content, ',"foo-bar",'));

        ob_start();
        $response->sendContent();
        $content2 = ob_get_clean();

        $this->assertEquals($content, $content2);
        $this->assertFalse(file_exists($file->getRealPath()));

        $all = [];
        $rows = str_getcsv($content2, PHP_EOL);
        foreach ($rows as $row) {
            $all[] = str_getcsv($row);
        }
        self::assertEquals(7, count($all));
        self::assertEquals(13, count($all[0]));
        self::assertEquals('foo', $all[4][8]);

        $expected = [
            0 => '2019.06.16 12:00',
            1 => '2019.06.16 12:06',
            2 => 'kevin',
            3 => 'Customer Name',
            4 => 'project name',
            5 => 'activity description',
            6 => '',
            7 => '',
            8 => 'foo,bar',
            9 => '€0.00',
            10 => '€84.00',
            11 => '00:06 h',
            12 => '€0.00',
        ];

        self::assertEquals($expected, $all[5]);
    }
}
