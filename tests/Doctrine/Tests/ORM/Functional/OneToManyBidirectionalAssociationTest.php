<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\ECommerce\ECommerceProduct;
use Doctrine\Tests\Models\ECommerce\ECommerceFeature;
use Doctrine\ORM\Mapping\AssociationMapping;

require_once __DIR__ . '/../../TestInit.php';

/**
 * Tests a bidirectional one-to-one association mapping (without inheritance).
 */
class OneToManyBidirectionalAssociationTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    private $product;
    private $firstFeature;
    private $secondFeature;

    protected function setUp()
    {
        $this->useModelSet('ecommerce');
        parent::setUp();
        $this->product = new ECommerceProduct();
        $this->product->setName('Doctrine Cookbook');
        $this->firstFeature = new ECommerceFeature();
        $this->firstFeature->setDescription('Model writing tutorial');
        $this->secondFeature = new ECommerceFeature();
        $this->secondFeature->setDescription('Annotations examples');
    }

    public function testSavesAOneToManyAssociationWithCascadeSaveSet() {
        $this->product->addFeature($this->firstFeature);
        $this->product->addFeature($this->secondFeature);
        $this->_em->persist($this->product);
        $this->_em->flush();
        
        $this->assertFeatureForeignKeyIs($this->product->getId(), $this->firstFeature);
        $this->assertFeatureForeignKeyIs($this->product->getId(), $this->secondFeature);
    }

    public function testSavesAnEmptyCollection()
    {
        $this->_em->persist($this->product);
        $this->_em->flush();

        $this->assertEquals(0, count($this->product->getFeatures()));
    }

    public function testDoesNotSaveAnInverseSideSet() {
        $this->product->brokenAddFeature($this->firstFeature);
        $this->_em->persist($this->product);
        $this->_em->flush();
        
        $this->assertFeatureForeignKeyIs(null, $this->firstFeature);
    }

    public function testRemovesOneToOneAssociation()
    {
        $this->product->addFeature($this->firstFeature);
        $this->product->addFeature($this->secondFeature);
        $this->_em->persist($this->product);

        $this->product->removeFeature($this->firstFeature);
        $this->_em->flush();

        $this->assertFeatureForeignKeyIs(null, $this->firstFeature);
        $this->assertFeatureForeignKeyIs($this->product->getId(), $this->secondFeature);
    }

    public function testEagerLoadsOneToManyAssociation()
    {
        $this->_createFixture();
        $query = $this->_em->createQuery('select p, f from Doctrine\Tests\Models\ECommerce\ECommerceProduct p join p.features f');
        $result = $query->getResult();
        $product = $result[0];
        $features = $product->getFeatures();
        
        $this->assertTrue($features[0] instanceof ECommerceFeature);
        $this->assertSame($product, $features[0]->getProduct());
        $this->assertEquals('Model writing tutorial', $features[0]->getDescription());
        $this->assertTrue($features[1] instanceof ECommerceFeature);
        $this->assertSame($product, $features[1]->getProduct());
        $this->assertEquals('Annotations examples', $features[1]->getDescription());
    }
    
    public function testLazyLoadsObjectsOnTheOwningSide()
    {
        $this->_createFixture();
        $this->_em->getConfiguration()->setAllowPartialObjects(false);
        $metadata = $this->_em->getClassMetadata('Doctrine\Tests\Models\ECommerce\ECommerceProduct');
        $metadata->getAssociationMapping('features')->fetchMode = AssociationMapping::FETCH_LAZY;

        $query = $this->_em->createQuery('select p from Doctrine\Tests\Models\ECommerce\ECommerceProduct p');
        $result = $query->getResult();
        $product = $result[0];
        $features = $product->getFeatures();
        
        $this->assertTrue($features[0] instanceof ECommerceFeature);
        $this->assertSame($product, $features[0]->getProduct());
        $this->assertEquals('Model writing tutorial', $features[0]->getDescription());
        $this->assertTrue($features[1] instanceof ECommerceFeature);
        $this->assertSame($product, $features[1]->getProduct());
        $this->assertEquals('Annotations examples', $features[1]->getDescription());
    }

    public function testLazyLoadsObjectsOnTheInverseSide()
    {
        $this->_createFixture();
        $this->_em->getConfiguration()->setAllowPartialObjects(false);
        $metadata = $this->_em->getClassMetadata('Doctrine\Tests\Models\ECommerce\ECommerceFeature');
        $metadata->getAssociationMapping('product')->fetchMode = AssociationMapping::FETCH_LAZY;

        $query = $this->_em->createQuery('select f from Doctrine\Tests\Models\ECommerce\ECommerceFeature f');
        $features = $query->getResult();
        
        $product = $features[0]->getProduct();
        $this->assertTrue($product instanceof ECommerceProduct);
        $this->assertSame('Doctrine Cookbook', $product->getName());
    }

    private function _createFixture()
    {
        $this->product->addFeature($this->firstFeature);
        $this->product->addFeature($this->secondFeature);
        $this->_em->persist($this->product);
        
        $this->_em->flush();
        $this->_em->clear();
    }

    public function assertFeatureForeignKeyIs($value, ECommerceFeature $feature) {
        $foreignKey = $this->_em->getConnection()->execute('SELECT product_id FROM ecommerce_features WHERE id=?', array($feature->getId()))->fetchColumn();
        $this->assertEquals($value, $foreignKey);
    }
}