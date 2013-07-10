<?php

namespace Sms16\Sms;

class sms16 {

  /**
   * @var string
   */
  private $Login;
  /**
   * @var string
   */
  private $Password;
  /**
   * @var string
   */
  private $Server;

  /**
   * @param string $Login
   * @param string $Password
   * @param string $Server
   */
  function __construct( $Login = '', $Password = '', $Server = "https://my2.sms16.ru/xml/" ) {

    if ( empty( $Server ) ) {
      throw new \Exception( __METHOD__ . ' - Недопустимое значение параметра $Server.' );
    }

    $this->Server = $Server;

    if ( !empty( $Login ) && !empty( $Password ) ) {
      $this->Login = $Login;
      $this->Password = $Password;
    } else {
      throw new \Exception( __METHOD__ . ' - Не указан параметр $Login и/или $Password.' );
    }
  }

  /**
   * @param string $url
   * @param $body
   * @return mixed
   * @throws Exception
   */
  private function http_request( $url, $body ) {
    $ch = curl_init();
    curl_setopt( $ch, CURLOPT_HTTPHEADER, array( 'Content-type: text/xml; charset=utf-8' ) );
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
    curl_setopt( $ch, CURLOPT_CRLF, true );
    curl_setopt( $ch, CURLOPT_POST, true );
    curl_setopt( $ch, CURLOPT_POSTFIELDS, $body );
    curl_setopt( $ch, CURLOPT_URL, $url );
    curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false ); // true
    curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 2 );
    curl_setopt( $ch, CURLOPT_CAINFO, realpath(getcwd().'/../') . "/app/config/thawtePrimaryRootCA.crt");

    $result = curl_exec( $ch );

    if ( $result === false ) {
      $error = curl_error( $ch );
      $errno = curl_errno( $ch );

      curl_close( $ch );

      throw new \Exception( $error, $errno );
    }
    curl_close( $ch );
    //throw new \Exception( $result );
    return $result;
  }

  /**
   * Converts SimpleXMLElement to multidimensional array
   * @param SimpleXMLElement $xml SimpleXMLElement to convert
   * @return array
   * @throws Exception
   */
  private function simpleXMLToArray( $xml ) {
    $ObjectNodes = array(
      'error',
      'money',
      'any_originator',
      'version',
      'originator'
    );

    if ( !( $xml instanceof \SimpleXMLElement ) ) {
      throw new \Exception( __METHOD__ . ' - Параметр $xml должен иметь тип SimpleXMLElement' );
    }

    $result = (object)null;
    $value = trim( (string)$xml );
    if ( !empty( $value ) ) {
      $result->value = $value;
    }

    foreach ( $xml->children() as $elementName => $child ) {
      if ( in_array( $elementName, $ObjectNodes ) ) {
        if ( $child->count || $child->attributes->count ) {
          $result->$elementName = $this->simpleXMLToArray( $child );
        } else {
          $result->$elementName = trim( (string)$child );
        }
      } else {
        $result->{$elementName}[] = $this->simpleXMLToArray( $child );
      }
    }
    foreach ( $xml->attributes() as $attr_name => $value ) {
      $result->$attr_name = trim( $value );
    }
    return $result;
  }

  /**
   * @param string $url
   * @param $body
   * @return array
   * @throws Exception
   */
  private function request( $method, $url, $body ) {
    return $this->decodeResponse( $this->http_request( $url, $body ), $method );
  }

  /**
   * @param string $Response
   * @param string $method
   * @return object
   * @throws Exception
   */
  public function decodeResponse( $Response, $method = __METHOD__ ) {
    if ( substr( $Response, 0, 5) != '<?xml' ) {
      throw new \Exception( $method . ' - Недопустимый формат ответа сервера sms16. Текст ответа: ' . $Response );
    }

    $xml = new \SimpleXMLElement( $Response );
    if ( $xml->getName() != 'response' ) {
      throw new \Exception( $method . ' - Недопустимый ответ сервера sms16. Содержимое ответа: ' . $Response );
    }

    $Result = (object)array( 'response' => $this->simpleXMLToArray( $xml ) );

    if ( isset( $Result->response->error ) ) {
      throw new \Exception( $method . ' - ' . $Result->response->error->value );
    }

    return $Result;
  }

  /**
   * @return object
   */
  public function version() {
    $Request = '<?xml version="1.0" encoding="utf-8"?><request></request>';
    $Result = $this->request( __METHOD__, $this->Server . 'version.php', $Request );
    return $Result;
  }

  /**
   * @return object
   */
  public function balance() {
    $Request = '<?xml version="1.0" encoding="utf-8"?><request><security><login value="' . $this->Login . '" /><password value="' . $this->Password . '" /></security></request>';
    $Result = $this->request( __METHOD__, $this->Server . 'balance.php', $Request );
    return $Result;
  }

  /**
   * @param int $from - unix timestamp
   * @param int $to - unix timestamp
   * @return object
   */
  public function incoming( $from, $to ) {
    $Request = '<?xml version="1.0" encoding="utf-8"?><request><security><login value="' . $this->Login . '" /><password value="' . $this->Password . '" /></security><time start="' . date(
      'Y-m-d H:i:s', $from
    ) . '" end="' . date( 'Y-m-d H:i:s', $to ) . '"/></request>';
    $Result = $this->request( __METHOD__, $this->Server . 'incoming.php', $Request );
    return $Result;
  }

  /**
   * @param array $id_sms
   * @return object
   * @throws Exception
   */
  public function state( $id_sms ) {
    if ( !is_array( $id_sms ) || count( $id_sms ) == 0 ) {
      throw new \Exception( __METHOD__ . ' - $id_sms не может быть пустым.' );
    }

    $Request = '<?xml version="1.0" encoding="utf-8"?><request><security><login value="' . $this->Login . '" /><password value="' . $this->Password . '" /></security><get_state>';
    foreach ( $id_sms as $id ) {
      if ( !isset( $id ) ) {
        throw new \Exception( __METHOD__ . ' - $id не может быть пустым.' );
      }
      $Request .= '<id_sms>' . $id . '</id_sms>';
    }
    $Request .= '</get_state></request>';
    $Result = $this->request( __METHOD__, $this->Server . 'state.php', $Request );
    return $Result;
  }

  /**
   * @return object
   */
  public function originator() {
    $Request = '<?xml version="1.0" encoding="utf-8"?><request><security><login value="' . $this->Login . '" /><password value="' . $this->Password . '" /></security></request>';
    $Result = $this->request( __METHOD__, $this->Server . 'originator.php', $Request );
    return $Result;
  }

  /**
   * @param array $messages
   * @return object
   * @throws Exception
   */
  public function send( $messages ) {
    $number_sms = 1;

    function formatAbonent( $abonent, $number_sms ) {
      if ( !is_object( $abonent ) ) {
        $abonent = (object)array( 'phone' => $abonent );
      }
      return '<abonent phone="' . $abonent->phone . '" number_sms="' . $number_sms . '"'
        . ( !empty( $abonent->phone_id ) ? ' phone_id="' . $abonent->phone_id . '"' : '' )
        . ( !empty( $abonent->time_send ) ? ' time_send="' . date( 'Y-m-d H:i:s', $abonent->time_send ) . '"' : '' )
        . '/>';
    }

    $Request = '<?xml version="1.0" encoding="utf-8"?><request><security><login value="' . $this->Login . '" /><password value="' . $this->Password . '" /></security>';
    foreach ( $messages as $message ) {
      if ( empty( $message->type ) ) {
        throw new \Exception( __METHOD__ . ' - Поле "$message->type" не может быть пустым.' );
      }
      if ( empty( $message->sender ) ) {
        throw new \Exception( __METHOD__ . ' - Поле "$message->sender" не может быть пустым.' );
      }
      if ( empty( $message->text ) ) {
        throw new \Exception( __METHOD__ . ' - Поле "$message->text" не может быть пустым.' );
      }
      if ( empty( $message->abonent ) ) {
        throw new \Exception( __METHOD__ . ' - Поле "$message->phone" не может быть пустым.' );
      }
      $Request .= '<message type="' . $message->type . '"><sender>' . $message->sender . '</sender>';
      $OptionalFields = array(
        'text', 'url', 'name', 'cell', 'work', 'fax', 'email', 'position', 'organization', 'post_office_box',
        'street', 'city', 'region', 'postal_code', 'country', 'additional'
      );
      foreach ( $OptionalFields as $field ) {
        if ( isset( $message->$field ) ) {
          $Request .= '<' . $field . '>' . htmlspecialchars( $message->$field ) . '</' . $field . '>';
        }
      }
      if ( is_array( $message->abonent ) ) {
        foreach ( $message->abonent as $abonent ) {
          $Request .= formatAbonent( $abonent, $number_sms );
          $number_sms++;
        }
      } else {
        $Request .= formatAbonent( $message->abonent, $number_sms );
        $number_sms++;
      }
      $Request .= '</message>';

    }
    $Request .= '</request>';
    $Result = $this->request( __METHOD__, $this->Server, $Request );
    //throw new \Exception(print_r($Result,true));
    return $Result;
  }

};

?>