<?php

namespace spec\Pim\Component\Catalog\Normalizer\Indexing\Product;

use PhpSpec\ObjectBehavior;
use Pim\Component\Catalog\Model\ProductInterface;
use Pim\Component\Catalog\Model\ProductValueCollectionInterface;
use Pim\Component\Catalog\Normalizer\Indexing\Product\PropertiesNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

class PropertiesNormalizerSpec extends ObjectBehavior
{
    function let(SerializerInterface $serializer)
    {
        $serializer->implement('Symfony\Component\Serializer\Normalizer\NormalizerInterface');
        $this->setSerializer($serializer);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(PropertiesNormalizer::class);
    }

    function it_support_products(ProductInterface $product)
    {
        $this->supportsNormalization(new \stdClass(), 'whatever')->shouldReturn(false);
        $this->supportsNormalization(new \stdClass(), 'indexing')->shouldReturn(false);
        $this->supportsNormalization($product, 'whatever')->shouldReturn(false);
        $this->supportsNormalization($product, 'indexing')->shouldReturn(true);
    }

    function it_normalizes_product_properties_with_empty_fields_and_values(
        ProductInterface $product,
        ProductValueCollectionInterface $productValueCollection
    ) {
        $product->getIdentifier()->willReturn('sku-001');

        $product->getValues()->willReturn($productValueCollection);
        $productValueCollection->isEmpty()->willReturn(true);

        $this->normalize($product, 'indexing')->shouldReturn(
            [
                'identifier' => 'sku-001',
                'values'     => [],
            ]
        );
    }

    function it_normalizes_product_values(
        $serializer,
        ProductInterface $product,
        ProductValueCollectionInterface $productValueCollection
    ) {
        $product->getIdentifier()->willReturn('sku-001');

        $product->getValues()
            ->shouldBeCalledTimes(2)
            ->willReturn($productValueCollection);
        $productValueCollection->isEmpty()->willReturn(false);

        $serializer->normalize($productValueCollection, 'indexing', [])
            ->willReturn(
                [
                    'a_size-decimal' => [
                        '<all_locales>' => [
                            '<all_channels>' => '10.51',
                        ],
                    ],
                ]
            );

        $this->normalize($product, 'indexing')->shouldReturn(
            [
                'identifier' => 'sku-001',
                'values'     => [
                    'a_size-decimal' => [
                        '<all_locales>' => [
                            '<all_channels>' => '10.51',
                        ],
                    ],
                ],

            ]
        );
    }
}
