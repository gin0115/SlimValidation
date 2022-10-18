<?php

namespace Awurth\SlimValidation\Tests;

use TypeError;
use Slim\Http\Request;
use Slim\Http\Environment;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Awurth\SlimValidation\Validator;
use Respect\Validation\Validator as V;

class ValidatorTest extends TestCase {

	/**
	 * @var array
	 */
	protected $array;

	/**
	 * @var TestObject
	 */
	protected $object;

	/**
	 * @var Request
	 */
	protected $request;

	/**
	 * @var Validator
	 */
	protected $validator;

	public function setUp(): void {
		$this->request = Request::createFromEnvironment(
			Environment::mock(
				array(
					'QUERY_STRING' => 'username=a_wurth&password=1234',
				)
			)
		);

		$this->array = array(
			'username' => 'a_wurth',
			'password' => '1234',
		);

		$this->object = new TestObject( 'private', 'protected', 'public' );

		$this->validator = new Validator();
	}

	public function testValidateWithoutRules() {
		$this->expectException( TypeError::class );
		$this->validator->validate(
			$this->request,
			array(
				'username',
			)
		);
	}

	public function testValidateWithOptionsWrongType() {
		$this->expectException( TypeError::class );
		$this->validator->validate(
			$this->request,
			array(
				'username' => null,
			)
		);
	}

	public function testValidateWithRulesWrongType() {
		$this->expectException( InvalidArgumentException::class );
		$this->validator->validate(
			$this->request,
			array(
				'username' => array(
					'rules' => null,
				),
			)
		);
	}

	public function testRequest() {
		$this->validator->request(
			$this->request,
			array(
				'username' => V::length( 6 ),
			)
		);

		$this->assertEquals( array( 'username' => 'a_wurth' ), $this->validator->getValues() );
		$this->assertEquals( 'a_wurth', $this->validator->getValue( 'username' ) );
		$this->assertTrue( $this->validator->isValid() );
	}

	public function testRequestWithGroup() {
		$this->validator->request(
			$this->request,
			array(
				'username' => V::length( 6 ),
			),
			'user'
		);

		$this->assertEquals(
			array(
				'user' => array(
					'username' => 'a_wurth',
				),
			),
			$this->validator->getValues()
		);
		$this->assertEquals( 'a_wurth', $this->validator->getValue( 'username', 'user' ) );
		$this->assertTrue( $this->validator->isValid() );
	}

	public function testArray() {
		$this->validator->array(
			$this->array,
			array(
				'username' => V::notBlank(),
				'password' => V::notBlank(),
			)
		);

		$this->assertEquals(
			array(
				'username' => 'a_wurth',
				'password' => '1234',
			),
			$this->validator->getValues()
		);
		$this->assertTrue( $this->validator->isValid() );
	}

	public function testObject() {
		$this->validator->object(
			$this->object,
			array(
				'privateProperty'   => V::notBlank(),
				'protectedProperty' => V::notBlank(),
				'publicProperty'    => V::notBlank(),
			)
		);

		$this->assertEquals(
			array(
				'privateProperty'   => 'private',
				'protectedProperty' => 'protected',
				'publicProperty'    => 'public',
			),
			$this->validator->getValues()
		);
		$this->assertTrue( $this->validator->isValid() );
	}

	public function testValue() {
		$this->validator->value( 2017, V::numericVal()->between( 2010, 2020 ), 'year' );

		$this->assertEquals( array( 'year' => 2017 ), $this->validator->getValues() );
		$this->assertTrue( $this->validator->isValid() );
	}

	public function testValidateWithErrors() {
		$this->validator->validate(
			$this->request,
			array(
				'username' => V::length( 8 ),
			)
		);

		$this->assertEquals( array( 'username' => 'a_wurth' ), $this->validator->getValues() );
		$this->assertEquals( 'a_wurth', $this->validator->getValue( 'username' ) );
		$this->assertFalse( $this->validator->isValid() );
		$this->assertEquals(
			array(
				'username' => array(
					'length' => '"a_wurth" must have a length greater than or equal to 8',
				),
			),
			$this->validator->getErrors()
		);
	}

	public function testValidateWithIndexedErrors() {
		$this->validator->setShowValidationRules( false );
		$this->validator->validate(
			$this->request,
			array(
				'username' => V::length( 8 ),
			)
		);

		$this->assertEquals( array( 'username' => 'a_wurth' ), $this->validator->getValues() );
		$this->assertEquals( 'a_wurth', $this->validator->getValue( 'username' ) );
		$this->assertFalse( $this->validator->isValid() );
		$this->assertEquals(
			array(
				'username' => array(
					'"a_wurth" must have a length greater than or equal to 8',
				),
			),
			$this->validator->getErrors()
		);
	}

	public function testValidateWithGroupedErrors() {
		$this->validator->validate(
			$this->request,
			array(
				'username' => V::length( 8 ),
			),
			'user'
		);

		$this->assertFalse( $this->validator->isValid() );
		$this->assertEquals(
			array(
				'user' => array(
					'username' => array(
						'length' => '"a_wurth" must have a length greater than or equal to 8',
					),
				),
			),
			$this->validator->getErrors()
		);
	}

	public function testValidateWithCustomDefaultMessage() {
		$this->validator->setDefaultMessages(
			array(
				'length' => 'Too short!',
			)
		);

		$this->validator->validate(
			$this->request,
			array(
				'username' => V::length( 8 ),
			)
		);

		$this->assertFalse( $this->validator->isValid() );
		$this->assertEquals(
			array(
				'username' => array(
					'length' => 'Too short!',
				),
			),
			$this->validator->getErrors()
		);
	}

	public function testValidateWithCustomDefaultMessageAndGroup() {
		$this->validator->setDefaultMessages(
			array(
				'length' => 'Too short!',
			)
		);

		$this->validator->validate(
			$this->request,
			array(
				'username' => V::length( 8 ),
			),
			'user'
		);

		$this->assertFalse( $this->validator->isValid() );
		$this->assertEquals(
			array(
				'user' => array(
					'username' => array(
						'length' => 'Too short!',
					),
				),
			),
			$this->validator->getErrors()
		);
	}

	public function testValidateWithCustomGlobalMessages() {
		$this->validator->validate(
			$this->request,
			array(
				'username' => V::length( 8 ),
				'password' => V::length( 8 ),
			),
			null,
			array(
				'length' => 'Too short!',
			)
		);

		$this->assertEquals(
			array(
				'username' => 'a_wurth',
				'password' => '1234',
			),
			$this->validator->getValues()
		);
		$this->assertFalse( $this->validator->isValid() );
		$this->assertEquals(
			array(
				'username' => array(
					'length' => 'Too short!',
				),
				'password' => array(
					'length' => 'Too short!',
				),
			),
			$this->validator->getErrors()
		);
	}

	public function testValidateWithCustomGlobalMessagesAndGroup() {
		$this->validator->validate(
			$this->request,
			array(
				'username' => V::length( 8 ),
				'password' => V::length( 8 ),
			),
			'user',
			array(
				'length' => 'Too short!',
			)
		);

		$this->assertEquals(
			array(
				'user' => array(
					'username' => 'a_wurth',
					'password' => '1234',
				),
			),
			$this->validator->getValues()
		);
		$this->assertFalse( $this->validator->isValid() );
		$this->assertEquals(
			array(
				'user' => array(
					'username' => array(
						'length' => 'Too short!',
					),
					'password' => array(
						'length' => 'Too short!',
					),
				),
			),
			$this->validator->getErrors()
		);
	}

	public function testValidateWithCustomDefaultAndGlobalMessages() {
		$this->validator->setDefaultMessage( 'length', 'Too short!' );

		$this->validator->validate(
			$this->request,
			array(
				'username' => V::length( 8 ),
				'password' => V::length( 8 )->alpha(),
			),
			null,
			array(
				'alpha' => 'Only letters are allowed',
			)
		);

		$this->assertEquals(
			array(
				'username' => 'a_wurth',
				'password' => '1234',
			),
			$this->validator->getValues()
		);
		$this->assertFalse( $this->validator->isValid() );
		$this->assertEquals(
			array(
				'username' => array(
					'length' => 'Too short!',
				),
				'password' => array(
					'length' => 'Too short!',
					'alpha'  => 'Only letters are allowed',
				),
			),
			$this->validator->getErrors()
		);
	}

	public function testValidateWithCustomDefaultAndGlobalMessagesAndGroup() {
		$this->validator->setDefaultMessage( 'length', 'Too short!' );

		$this->validator->validate(
			$this->request,
			array(
				'username' => V::length( 8 ),
				'password' => V::length( 8 )->alpha(),
			),
			'user',
			array(
				'alpha' => 'Only letters are allowed',
			)
		);

		$this->assertEquals(
			array(
				'user' => array(
					'username' => 'a_wurth',
					'password' => '1234',
				),
			),
			$this->validator->getValues()
		);
		$this->assertFalse( $this->validator->isValid() );
		$this->assertEquals(
			array(
				'user' => array(
					'username' => array(
						'length' => 'Too short!',
					),
					'password' => array(
						'length' => 'Too short!',
						'alpha'  => 'Only letters are allowed',
					),
				),
			),
			$this->validator->getErrors()
		);
	}

	public function testValidateWithCustomIndividualMessage() {
		$this->validator->validate(
			$this->request,
			array(
				'username' => array(
					'rules'    => V::length( 8 ),
					'messages' => array(
						'length' => 'Too short!',
					),
				),
				'password' => V::length( 8 ),
			)
		);

		$this->assertEquals(
			array(
				'username' => 'a_wurth',
				'password' => '1234',
			),
			$this->validator->getValues()
		);
		$this->assertFalse( $this->validator->isValid() );
		$this->assertEquals(
			array(
				'username' => array(
					'length' => 'Too short!',
				),
				'password' => array(
					'length' => '"1234" must have a length greater than or equal to 8',
				),
			),
			$this->validator->getErrors()
		);
	}

	public function testValidateWithCustomIndividualMessageAndGroup() {
		$this->validator->validate(
			$this->request,
			array(
				'username' => array(
					'rules'    => V::length( 8 ),
					'messages' => array(
						'length' => 'Too short!',
					),
				),
				'password' => V::length( 8 ),
			),
			'user'
		);

		$this->assertEquals(
			array(
				'user' => array(
					'username' => 'a_wurth',
					'password' => '1234',
				),
			),
			$this->validator->getValues()
		);
		$this->assertFalse( $this->validator->isValid() );
		$this->assertEquals(
			array(
				'user' => array(
					'username' => array(
						'length' => 'Too short!',
					),
					'password' => array(
						'length' => '"1234" must have a length greater than or equal to 8',
					),
				),
			),
			$this->validator->getErrors()
		);
	}

	public function testValidateWithWrongCustomSingleMessageType() {
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Expected custom message to be of type string, integer given' );
		$this->validator->validate(
			$this->request,
			array(
				'username' => array(
					'rules'   => V::length( 8 )->alnum(),
					'message' => 10,
				),
			)
		);
	}

	public function testValidateWithCustomSingleMessage() {
		$this->validator->validate(
			$this->request,
			array(
				'username' => array(
					'rules'    => V::length( 8 )->alnum(),
					'message'  => 'Bad username.',
					'messages' => array(
						'length' => 'Too short!',
					),
				),
				'password' => array(
					'rules'    => V::length( 8 ),
					'messages' => array(
						'length' => 'Too short!',
					),
				),
			)
		);

		$this->assertEquals(
			array(
				'username' => 'a_wurth',
				'password' => '1234',
			),
			$this->validator->getValues()
		);
		$this->assertFalse( $this->validator->isValid() );
		$this->assertEquals(
			array(
				'username' => array(
					'Bad username.',
				),
				'password' => array(
					'length' => 'Too short!',
				),
			),
			$this->validator->getErrors()
		);
	}

	public function testIsValidWithErrors() {
		$this->validator->setErrors( array( 'error' ) );

		$this->assertFalse( $this->validator->isValid() );
	}

	public function testIsValidWithoutErrors() {
		$this->validator->removeErrors();

		$this->assertTrue( $this->validator->isValid() );
	}

	public function testAddError() {
		$this->validator->addError( 'param', 'message' );

		$this->assertEquals(
			array(
				'param' => array(
					'message',
				),
			),
			$this->validator->getErrors()
		);
	}

	public function testGetFirstError() {
		$this->assertEquals( '', $this->validator->getFirstError( 'username' ) );

		$this->validator->setErrors(
			array(
				'param'    => array(
					'notBlank' => 'Required',
				),
				'username' => array(
					'alnum'  => 'Only letters and numbers are allowed',
					'length' => 'Too short!',
				),
			)
		);

		$this->assertEquals( 'Only letters and numbers are allowed', $this->validator->getFirstError( 'username' ) );

		$this->validator->setErrors(
			array(
				'param'    => array(
					'Required',
				),
				'username' => array(
					'This field is required',
					'Only letters and numbers are allowed',
				),
			)
		);

		$this->assertEquals( 'This field is required', $this->validator->getFirstError( 'username' ) );
	}

	public function testGetErrors() {
		$this->assertEquals( array(), $this->validator->getErrors( 'username' ) );

		$this->validator->setErrors(
			array(
				'param'    => array(
					'Required',
				),
				'username' => array(
					'This field is required',
					'Only letters and numbers are allowed',
				),
			)
		);

		$this->assertEquals(
			array(
				'This field is required',
				'Only letters and numbers are allowed',
			),
			$this->validator->getErrors( 'username' )
		);
	}

	public function testGetError() {
		$this->assertEquals( '', $this->validator->getError( 'username', 'length' ) );

		$this->validator->setErrors(
			array(
				'username' => array(
					'alnum'  => 'Only letters and numbers are allowed',
					'length' => 'Too short!',
				),
			)
		);

		$this->assertEquals( 'Too short!', $this->validator->getError( 'username', 'length' ) );
	}

	public function testSetValues() {
		$this->assertEquals( array(), $this->validator->getValues() );

		$this->validator->setValues(
			array(
				'username' => 'awurth',
				'password' => 'pass',
			)
		);

		$this->assertEquals(
			array(
				'username' => 'awurth',
				'password' => 'pass',
			),
			$this->validator->getValues()
		);
	}

	public function testSetDefaultMessage() {
		$this->assertEquals( array(), $this->validator->getDefaultMessages() );

		$this->validator->setDefaultMessage( 'length', 'Too short!' );

		$this->assertEquals(
			array(
				'length' => 'Too short!',
			),
			$this->validator->getDefaultMessages()
		);
	}

	public function testSetErrors() {
		$this->assertEquals( array(), $this->validator->getErrors() );

		$this->validator->setErrors(
			array(
				'notBlank' => 'Required',
				'length'   => 'Too short!',
			),
			'username'
		);

		$this->assertEquals(
			array(
				'username' => array(
					'notBlank' => 'Required',
					'length'   => 'Too short!',
				),
			),
			$this->validator->getErrors()
		);
	}

	public function testSetShowValidationRules() {
		$this->assertTrue( $this->validator->getShowValidationRules() );

		$this->validator->setShowValidationRules( false );

		$this->assertFalse( $this->validator->getShowValidationRules() );
	}

	public function testValidateInvalidSearcher() {
		$this->validator->value(
			'FR',
			array(
				'rules' => V::subdivisionCode( 'US' ),
			),
			'subdivision'
		);

		$this->assertFalse( $this->validator->isValid() );
	}
}
