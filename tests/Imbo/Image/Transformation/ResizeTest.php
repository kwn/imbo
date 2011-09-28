<?php
/**
 * Imbo
 *
 * Copyright (c) 2011 Christer Edvartsen <cogo@starzinger.net>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to
 * deal in the Software without restriction, including without limitation the
 * rights to use, copy, modify, merge, publish, distribute, sublicense, and/or
 * sell copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * * The above copyright notice and this permission notice shall be included in
 *   all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * @package Imbo
 * @subpackage Unittests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/imbo
 */

namespace Imbo\Image\Transformation;

use Mockery as m;

/**
 * @package Imbo
 * @subpackage Unittests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/imbo
 */
class ResizeTest extends TransformationTests {
    protected function getTransformation() {
        return new Resize(1, 2);
    }

    public function testApplyToImageWithBothParams() {
        $image = m::mock('Imbo\Image\ImageInterface');
        $image->shouldReceive('getBlob')->once()->andReturn(file_get_contents(__DIR__ . '/../../_files/image.png'));
        $image->shouldReceive('setBlob')->once()->with(m::type('string'))->andReturn($image);
        $image->shouldReceive('setWidth')->once()->with(200)->andReturn($image);
        $image->shouldReceive('setHeight')->once()->with(100)->andReturn($image);
        $image->shouldReceive('getExtension')->once()->andReturn('png');

        $transformation = new Resize(200, 100);
        $transformation->applyToImage($image);
    }

    public function testApplyToImageWithOnlyWidth() {
        $image = m::mock('Imbo\Image\ImageInterface');
        $image->shouldReceive('getBlob')->once()->andReturn(file_get_contents(__DIR__ . '/../../_files/image.png'));
        $image->shouldReceive('setBlob')->once()->with(m::type('string'))->andReturn($image);
        $image->shouldReceive('setWidth')->once()->with(200)->andReturn($image);
        $image->shouldReceive('setHeight')->once()->with(m::type('int'))->andReturn($image);
        $image->shouldReceive('getExtension')->once()->andReturn('png');

        $transformation = new Resize(200);
        $transformation->applyToImage($image);
    }

    public function testApplyToImageWithOnlyHeight() {
        $image = m::mock('Imbo\Image\ImageInterface');
        $image->shouldReceive('getBlob')->once()->andReturn(file_get_contents(__DIR__ . '/../../_files/image.png'));
        $image->shouldReceive('setBlob')->once()->with(m::type('string'))->andReturn($image);
        $image->shouldReceive('setWidth')->once()->with(m::type('int'))->andReturn($image);
        $image->shouldReceive('setHeight')->once()->with(200)->andReturn($image);
        $image->shouldReceive('getExtension')->once()->andReturn('png');

        $transformation = new Resize(null, 200);
        $transformation->applyToImage($image);
    }

    public function testApplyToImageUrl() {
        $url = m::mock('Imbo\Client\ImageUrl');
        $url->shouldReceive('append')->with(m::on(function ($string) {
            return (preg_match('/^resize:/', $string) && strstr($string, 'width=100') && strstr($string, 'height=200'));
        }))->once();
        $transformation = new Resize(100, 200);
        $transformation->applyToImageUrl($url);
    }
}