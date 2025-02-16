<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\saml\Auth\Process;

use PHPUnit\Framework\TestCase;
use SimpleSAML\Error;
use SimpleSAML\Module\saml\Auth\Process\NameIDAttribute;
use SimpleSAML\SAML2\Constants as C;
use SimpleSAML\SAML2\XML\saml\NameID;

/**
 * Test for the saml:NameIDAttribute filter.
 *
 * @covers \SimpleSAML\Module\saml\Auth\Process\NameIDAttribute
 *
 * @package SimpleSAMLphp
 */
class NameIDAttributeTest extends TestCase
{
    /**
     * Helper function to run the filter with a given configuration.
     *
     * @param array $config  The filter configuration.
     * @param array $request  The request state.
     * @return array  The state array after processing.
     */
    private function processFilter(array $config, array $request): array
    {
        $filter = new NameIDAttribute($config, null);
        $filter->process($request);
        return $request;
    }


    /**
     * Test minimal configuration.
     */
    public function testMinimalConfig(): void
    {
        $config = [];
        $spId = 'eugeneSP';
        $idpId = 'eugeneIdP';


        $nameId = new NameID();
        $nameId->setValue('eugene@oombaas');
        $nameId->setFormat(C::NAMEID_PERSISTENT);
        $nameId->setNameQualifier($idpId);
        $nameId->setSPNameQualifier($spId);

        $request = [
            'Source'     => [
                'entityid' => $spId,
            ],
            'Destination' => [
                'entityid' => $idpId,
            ],
            'saml:sp:NameID' => $nameId,
        ];
        $result = $this->processFilter($config, $request);
        $this->assertEquals("{$idpId}!{$spId}!{$nameId->getValue()}", $result['Attributes']['nameid'][0]);
    }


    /**
     * Test custom attribute name.
     */
    public function testCustomAttributeName(): void
    {
        $attributeName = 'eugeneNameIDAttribute';
        $config = ['attribute' => $attributeName];
        $spId = 'eugeneSP';
        $idpId = 'eugeneIdP';

        $nameId = new NameID();
        $nameId->setValue('eugene@oombaas');
        $nameId->setFormat(C::NAMEID_PERSISTENT);
        $nameId->setNameQualifier($idpId);
        $nameId->setSPNameQualifier($spId);

        $request = [
            'Source'     => [
                'entityid' => $spId,
            ],
            'Destination' => [
                'entityid' => $idpId,
            ],
            'saml:sp:NameID' => $nameId,
        ];
        $result = $this->processFilter($config, $request);
        $this->assertTrue(isset($result['Attributes'][$attributeName]));
        $this->assertEquals("{$idpId}!{$spId}!{$nameId->getValue()}", $result['Attributes'][$attributeName][0]);
    }


    /**
     * Test custom format.
     */
    public function testFormat(): void
    {
        $config = ['format' => '%V!%%'];
        $spId = 'eugeneSP';
        $idpId = 'eugeneIdP';

        $nameId = new NameID();
        $nameId->setValue('eugene@oombaas');
        $nameId->setFormat(C::NAMEID_PERSISTENT);
        $nameId->setNameQualifier($idpId);
        $nameId->setSPNameQualifier($spId);

        $request = [
            'Source'     => [
                'entityid' => $spId,
            ],
            'Destination' => [
                'entityid' => $idpId,
            ],
            'saml:sp:NameID' => $nameId,
        ];
        $result = $this->processFilter($config, $request);
        $this->assertEquals("{$nameId->getValue()}!%", $result['Attributes']['nameid'][0]);
    }


    /**
     * Test invalid format throws an exception.
     */
    public function testInvalidFormatThrowsException(): void
    {
        $config = ['format' => '%X'];
        $spId = 'eugeneSP';
        $idpId = 'eugeneIdP';

        $nameId = new NameID();
        $nameId->setValue('eugene@oombaas');

        $request = [
            'Source'     => [
                'entityid' => $spId,
            ],
            'Destination' => [
                'entityid' => $idpId,
            ],
            'saml:sp:NameID' => $nameId,
        ];

        $this->expectException(Error\Exception::class);
        $this->expectExceptionMessage('NameIDAttribute: Invalid replacement: "%X"');

        $this->processFilter($config, $request);
    }


    /**
     * Test invalid request silently continues, leaving the state untouched
     */
    public function testInvalidRequestLeavesStateUntouched(): void
    {
        $config = ['format' => '%V!%F'];
        $spId = 'eugeneSP';
        $idpId = 'eugeneIdP';

        $request = [
            'Source'     => [
                'entityid' => $spId,
            ],
            'Destination' => [
                'entityid' => $idpId,
            ],
        ];

        $pre = $request;
        $this->processFilter($config, $request);
        $this->assertEquals($pre, $request);
    }


    /**
     * Test custom attribute name with format.
     */
    public function testCustomAttributeNameAndFormat(): void
    {
        $attributeName = 'eugeneNameIDAttribute';
        $config = ['attribute' => $attributeName, 'format' => '%V'];
        $spId = 'eugeneSP';
        $idpId = 'eugeneIdP';

        $nameId = new NameID();
        $nameId->setValue('eugene@oombaas');
        $nameId->setFormat(C::NAMEID_PERSISTENT);
        $nameId->setNameQualifier($idpId);
        $nameId->setSPNameQualifier($spId);

        $request = [
            'Source'     => [
                'entityid' => $spId,
            ],
            'Destination' => [
                'entityid' => $idpId,
            ],
            'saml:sp:NameID' => $nameId,
        ];
        $result = $this->processFilter($config, $request);
        $this->assertTrue(isset($result['Attributes'][$attributeName]));
        $this->assertEquals("{$nameId->getValue()}", $result['Attributes'][$attributeName][0]);
    }


    /**
     * Test overriding NameID Format/NameQualifier/SPNameQualifier with defaults.
     */
    public function testOverrideNameID(): void
    {
        $spId = 'eugeneSP';
        $idpId = 'eugeneIdP';

        $nameId = new NameID();
        $nameId->setValue('eugene@oombaas');

        $request = [
            'Source'     => [
                'entityid' => $spId,
            ],
            'Destination' => [
                'entityid' => $idpId,
            ],
            'saml:sp:NameID' => $nameId,
        ];
        $this->processFilter([], $request);
        $this->assertEquals("{$nameId->getFormat()}", C::NAMEID_UNSPECIFIED);
        $this->assertEquals("{$nameId->getNameQualifier()}", $idpId);
        $this->assertEquals("{$nameId->getSPNameQualifier()}", $spId);
    }
}
