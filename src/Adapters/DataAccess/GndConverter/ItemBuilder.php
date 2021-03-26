<?php

declare( strict_types = 1 );

namespace DNB\GND\Adapters\DataAccess\GndConverter;

use DNB\WikibaseConverter\GndItem;
use DNB\WikibaseConverter\GndQualifier;
use DNB\WikibaseConverter\IdConverter;
use RuntimeException;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\StatementList;

/**
 * Builds a Wikibase Item (using the Wikibase DataModel classes) from
 * an item representation coming from the GND Wikibase Converter library.
 */
class ItemBuilder {

	private const GND_ID = 'P150';

	private ValueBuilder $valueBuilder;

	public function __construct( ValueBuilder $valueBuilder ) {
		$this->valueBuilder = $valueBuilder;
	}

	public function build( GndItem $gndItem ): Item {
		$id = $gndItem->getNumericId();

		if ( $id === null ) {
			throw new RuntimeException( 'No item id found' );
		}

		return new Item(
			ItemId::newFromNumber( $id ),
			null,
			null,
			$this->statementsFromGndItem( $gndItem )
		);
	}

	private function statementsFromGndItem( GndItem $gndItem ): StatementList {
		$statements = new StatementList();

		foreach ( $gndItem->getPropertyIds() as $id ) {
			foreach ( $gndItem->getStatementsForProperty( $id ) as $gndStatement ) {
				$statements->addNewStatement(
					$this->newWikibaseQualifier( $id, $gndStatement->getValue() ),
					array_map(
						fn( GndQualifier $qualifier ) => $this->newWikibaseQualifier(
							$qualifier->getPropertyId(),
							$qualifier->getValue()
						),
						$gndStatement->getQualifiers()
					)
				);
			}
		}

		return $statements;
	}

	private function newWikibaseQualifier( string $propertyId, string $value ): PropertyValueSnak {
		return new PropertyValueSnak(
			new PropertyId( $propertyId ),
			// TODO: look up property type based on ID (PropertyDataTypeLookup)
			$this->valueBuilder->stringToDataValue( $value, 'string' )
		);
	}

}
