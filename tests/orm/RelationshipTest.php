<?php

namespace Alchemy\tests;
use Alchemy\orm\Session;
use Alchemy\orm\ManyToOne;
use Alchemy\orm\OneToMany;
use Datetime;


class RelationshipTest extends BaseTest {
    private $session;


    public function setUp() {
        $engine = $this->getSQLiteEngine();
        $session = new Session($engine);

        $session->ddl()->dropAll();
        $session->ddl()->createAll();

        $this->session = $session;
    }


    public function testRelationshipDefinition() {
        $l = Language::list_relationships();
        $f = UploadedFile::list_relationships();

        $files = $l['Files'];
        $this->assertTrue($files instanceof OneToMany);
        $this->assertEquals($files->getBackref(), 'Language');
        $this->assertEquals($files->getName(), 'Files');
        $this->assertEquals($files->getForeignKey()->getSourceTable()->getName(), 'Language');
        $this->assertFalse($files->hasSingleObjectConstraint());
        $this->assertTrue($files->isParent());

        $language = $f['Language'];
        $this->assertTrue($language instanceof ManyToOne);
        $this->assertEquals($language->getBackref(), 'Files');
        $this->assertEquals($language->getName(), 'Language');
        $this->assertEquals($language->getForeignKey()->getSourceTable()->getName(), 'Language');
        $this->assertTrue($language->hasSingleObjectConstraint());
        $this->assertFalse($language->isParent());

        $parent = $l['Parent'];
        $this->assertTrue($parent instanceof ManyToOne);
        $this->assertEquals($parent->getBackref(), 'Children');
        $this->assertEquals($parent->getName(), 'Parent');
        $this->assertEquals($parent->getForeignKey()->getSourceTable()->getName(), 'Language');
        $this->assertTrue($parent->hasSingleObjectConstraint());
        $this->assertFalse($parent->isParent());

        $children = $l['Children'];
        $this->assertTrue($children instanceof OneToMany);
        $this->assertEquals($children->getBackref(), 'Parent');
        $this->assertEquals($children->getName(), 'Children');
        $this->assertEquals($children->getForeignKey()->getSourceTable()->getName(), 'Language');
        $this->assertFalse($children->hasSingleObjectConstraint());
        $this->assertTrue($children->isParent());
    }


    public function testRelationSetFullTransient() {
        $english = new Language();
        $english->ISO2Code = 'en';
        $english->LatestChangeStamp = new DateTime("1984-01-01");

        $spanish = new Language();
        $spanish->ISO2Code = 'es';
        $spanish->LatestChangeStamp = new DateTime("1984-01-01");

        $spanish->Parent = $english;

        $this->session->add($english);
        $this->session->commit();

        $s = $english->Children->one();
        $this->assertEquals($s->ISO2Code, 'es');
        $this->assertEquals($s->Parent->ISO2Code, 'en');
    }


    public function testRelationSetNonTransient() {
        $english = new Language();
        $english->ISO2Code = 'en';
        $english->LatestChangeStamp = new DateTime("1984-01-01");
        $this->session->add($english);

        $spanish = new Language();
        $spanish->ISO2Code = 'es';
        $spanish->LatestChangeStamp = new DateTime("1984-01-01");
        $this->session->add($spanish);
        $this->session->commit();

        $spanish->Parent = $english;

        $this->session->commit();

        $s = $english->Children->one();
        $this->assertEquals($s->ISO2Code, 'es');
        $this->assertEquals($s->Parent->ISO2Code, 'en');
    }


    public function testRelationSetPartialTransient() {
        $english = new Language();
        $english->ISO2Code = 'en';
        $english->LatestChangeStamp = new DateTime("1984-01-01");
        $this->session->add($english);

        $spanish = new Language();
        $spanish->ISO2Code = 'es';
        $spanish->LatestChangeStamp = new DateTime("1984-01-01");

        $spanish->Parent = $english;

        $this->session->commit();

        $s = $english->Children->one();
        $this->assertEquals($s->ISO2Code, 'es');
        $this->assertEquals($s->Parent->ISO2Code, 'en');
    }


    public function testRelationAddFullTransient() {
        $english = new Language();
        $english->ISO2Code = 'en';
        $english->LatestChangeStamp = new DateTime("1984-01-01");

        $spanish = new Language();
        $spanish->ISO2Code = 'es';
        $spanish->LatestChangeStamp = new DateTime("1984-01-01");

        $english->Children->add($spanish);

        $this->session->add($english);
        $this->session->commit();

        $s = $english->Children->one();
        $this->assertEquals($s->ISO2Code, 'es');
        $this->assertEquals($s->Parent->ISO2Code, 'en');
    }


    public function testRelationAddNonTransient() {
        $english = new Language();
        $english->ISO2Code = 'en';
        $english->LatestChangeStamp = new DateTime("1984-01-01");
        $this->session->add($english);

        $spanish = new Language();
        $spanish->ISO2Code = 'es';
        $spanish->LatestChangeStamp = new DateTime("1984-01-01");
        $this->session->add($spanish);
        $this->session->commit();

        $english->Children->add($spanish);
        $this->session->commit();

        $s = $english->Children->one();
        $this->assertEquals($s->ISO2Code, 'es');
        $this->assertEquals($s->Parent->ISO2Code, 'en');
    }


    public function testRelationAddPartialTransient() {
        $english = new Language();
        $english->ISO2Code = 'en';
        $english->LatestChangeStamp = new DateTime("1984-01-01");
        $this->session->add($english);

        $spanish = new Language();
        $spanish->ISO2Code = 'es';
        $spanish->LatestChangeStamp = new DateTime("1984-01-01");

        $english->Children->add($spanish);
        $this->session->commit();

        $s = $english->Children->one();
        $this->assertEquals($s->ISO2Code, 'es');
        $this->assertEquals($s->Parent->ISO2Code, 'en');
    }


    public function testOneToOne() {
        $mandarin = new Language();
        $mandarin->ISO2Code = 'zh';
        $mandarin->LatestChangeStamp = new DateTime("1984-01-01");

        $simplified = new Language();
        $simplified->ISO2Code = 'hans';
        $simplified->LatestChangeStamp = new DateTime("1984-01-01");
        $simplified->SpokenLanguage = $mandarin;

        $this->session->add($simplified);
        $this->session->commit();

        $this->assertEquals($simplified->SpokenLanguage->ISO2Code, 'zh');
        $this->assertEquals($mandarin->WrittenLanguage->ISO2Code, 'hans');
    }
}