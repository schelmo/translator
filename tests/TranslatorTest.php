<?php

/*
 * This file is part of Laravel Translator.
 *
 * (c) Vincent Klaiber <hello@vinkla.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Vinkla\Tests\Translator;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use ReflectionClass;
use Vinkla\Translator\IsTranslatable;

/**
 * This is the translator test class.
 *
 * @author Vincent Klaiber <vincent@schimpanz.com>
 */
class TranslatorTest extends AbstractTestCase
{
    public function testInterface()
    {
        $article = new ReflectionClass(Article::class);
        $this->assertTrue($article->implementsInterface(IsTranslatable::class));
    }

    public function testHasMany()
    {
        $article = Article::first();
        $this->assertSame(2, $article->translations()->count());
    }

    public function testTranslate()
    {
        $article = Article::first();
        $this->assertSame($article->translate('en')->title, 'Use the force Harry');
        $this->assertSame($article->translate('sv')->title, 'Använd kraften Harry');
    }

    public function testLocale()
    {
        $article = Article::first();
        $locale = $this->getProtectedMethod($article, 'getLocale');
        $this->assertSame('sv', $locale);
        $fallback = $this->getProtectedMethod($article, 'getFallback');
        $this->assertSame('en', $fallback);
    }

    public function testFallback()
    {
        $article = Article::first();
        $this->assertSame($article->translate('de')->title, 'Use the force Harry');
        $this->assertSame($article->translate('de', true)->title, 'Use the force Harry');
        $this->assertSame($article->translate('de', false)->title, null);

        App::setLocale('sv');
        $this->assertSame($article->translate('sv', false)->title, 'Använd kraften Harry');
        $this->assertSame($article->translate('nl', false)->title, null);
        $this->assertSame($article->translate('sv', false)->title, 'Använd kraften Harry');
        $this->assertSame(App::getLocale(), 'sv');
    }

    public function testSetLocale()
    {
        $article = Article::first();
        $this->assertSame($article->title, 'Använd kraften Harry');
        $this->assertSame($article->translate()->title, 'Använd kraften Harry');
        App::setLocale('en');
        $this->assertSame($article->title, 'Use the force Harry');
        $this->assertSame($article->translate()->title, 'Use the force Harry');
    }

    public function testCachedTranslations()
    {
        $article = Article::first();
        $translations = ['en' => $article->translate('en'), 'sv' => $article->translate('sv')];
        $cache = $this->getProtectedProperty($article, 'cache');
        $this->assertCount(2, $cache);
        $this->assertSame($translations, $cache);
        DB::enableQueryLog();
        $article->translate('en');
        $this->assertEmpty(DB::getQueryLog());
    }

    public function testGetAttributes()
    {
        $article = Article::first();
        $this->assertSame($article->translate()->title, 'Använd kraften Harry');
        $this->assertSame($article->title, 'Använd kraften Harry');
    }

    public function testSetAttributes()
    {
        App::setLocale('en');
        $article = Article::first();
        $this->assertSame($article->title, 'Use the force Harry');
        $article->title = 'I\'m your father Hagrid';
        $this->assertSame($article->title, 'I\'m your father Hagrid');
        $this->assertSame($article->translate()->title, 'I\'m your father Hagrid');
        $this->assertSame($article->translate('sv')->title, 'Använd kraften Harry');
    }

    public function testCreate()
    {
        App::setLocale('en');
        $article = Article::create(['title' => 'Whoa. This is heavy.', 'thumbnail' => 'http://i.imgur.com/tyfwfEX.jpg']);
        $this->seeInDatabase('article_translations', ['title' => 'Whoa. This is heavy.', 'article_id' => $article->id, 'locale' => 'en']);
        $this->seeInDatabase('articles', ['thumbnail' => 'http://i.imgur.com/tyfwfEX.jpg']);
        App::setLocale('de');
        $article = Article::create(['title' => 'Whoa. Das ist schwer.', 'thumbnail' => 'http://i.imgur.com/tyfwfEX.jpg']);
        $this->seeInDatabase('article_translations', ['title' => 'Whoa. Das ist schwer.', 'article_id' => $article->id, 'locale' => 'de']);
        $this->seeInDatabase('articles', ['thumbnail' => 'http://i.imgur.com/tyfwfEX.jpg']);
    }

    public function testUpdate()
    {
        App::setLocale('en');
        $article = Article::find(1);
        $article->title = 'Whoa. This is heavy.';
        $article->save();
        $this->seeInDatabase('article_translations', ['title' => 'Whoa. This is heavy.', 'article_id' => $article->id, 'locale' => 'en']);
        App::setLocale('sv');
        $article->update(['title' => 'Whoa. Detta är tung.']);
        $this->seeInDatabase('article_translations', ['title' => 'Whoa. Detta är tung.', 'article_id' => $article->id, 'locale' => 'sv']);
        App::setLocale('de');
        $article->update(['title' => 'Whoa. Das ist schwer.']);
        $this->seeInDatabase('article_translations', ['title' => 'Whoa. Das ist schwer.', 'article_id' => $article->id, 'locale' => 'de']);
    }

    public function testDeleteTranslations()
    {
        $article = Article::first();
        $article->translations()->delete();
        $this->assertSame(1, Article::count());
        $this->assertSame(0, ArticleTranslation::count());
    }

    public function testDeleteParent()
    {
        Article::first()->delete();
        $this->assertSame(0, Article::count());
        $this->assertSame(0, ArticleTranslation::count());
    }

    protected function getProtectedMethod($instance, $method, $parameters = null)
    {
        $rc = new ReflectionClass($instance);
        $method = $rc->getMethod($method);
        $method->setAccessible(true);

        return $method->invoke($instance, $parameters);
    }

    protected function getProtectedProperty($instance, $property)
    {
        $rc = new ReflectionClass($instance);
        $property = $rc->getProperty($property);
        $property->setAccessible(true);

        return $property->getValue($instance);
    }
}
