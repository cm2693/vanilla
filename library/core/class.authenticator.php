<?php if (!defined('APPLICATION')) { exit(); 
}

/**
 * Authenticator Module: Base Class
 *
 * @author    Tim Gunter <tim@vanillaforums.com>
 * @copyright 2003 Vanilla Forums, Inc
 * @license   http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package   Garden
 * @since     2.0.10
 * @abstract
 */

abstract class Gdn_Authenticator extends Gdn_Pluggable
{
   
    const DATA_NONE            = 'data none';
    const DATA_FORM            = 'data form';
    const DATA_REQUEST         = 'data request';
    const DATA_COOKIE          = 'data cookie';
   
    const MODE_REPEAT          = 'already logged in';
    const MODE_GATHER          = 'gather';
    const MODE_VALIDATE        = 'validate';
    const MODE_NOAUTH          = 'no foreign identity';
   
    const AUTH_DENIED          = 0;
    const AUTH_PERMISSION      = -1;
    const AUTH_INSUFFICIENT    = -2;
    const AUTH_PARTIAL         = -3;
    const AUTH_SUCCESS         = -4;
    const AUTH_ABORTED         = -5;
    const AUTH_CREATED         = -6;
   
    const HANDSHAKE_JS         = 'javascript';
    const HANDSHAKE_DIRECT     = 'direct';
    const HANDSHAKE_IMAGE      = 'image';
   
    const REACT_RENDER         = 1;
    const REACT_EXIT           = 2;
    const REACT_REDIRECT       = 3;
    const REACT_REMOTE         = 4;
   
    const URL_REGISTER         = 'RegisterUrl';
    const URL_SIGNIN           = 'SignInUrl';
    const URL_SIGNOUT          = 'SignOutUrl';
   
    const URL_REMOTE_REGISTER  = 'RemoteRegisterUrl';
    const URL_REMOTE_SIGNIN    = 'RemoteSignInUrl';
    const URL_REMOTE_SIGNOUT   = 'RemoteSignOutUrl';
   
    const KEY_TYPE_TOKEN       = 'token';
    const KEY_TYPE_PROVIDER    = 'provider';
   
    /**
    * Alias of the authentication scheme to use, e.g. "password" or "openid"
    */
    protected $_AuthenticationSchemeAlias = null;
      
    protected $_DataSourceType = self::DATA_FORM;
    protected $_DataSource = null;
    public $_DataHooks = array();

    /**
    * Returns the unique id assigned to the user in the database, 0 if the
    * username/password combination weren't found, or -1 if the user does not
    * have permission to sign in.
    */
    abstract public function Authenticate();
    abstract public function CurrentStep();
    abstract public function DeAuthenticate();
    abstract public function WakeUp();
   
    // What to do if entry/auth/* is called while the user is logged out. Should normally be REACT_RENDER
    abstract public function LoginResponse();
   
    // What to do after part 1 of a 2 part authentication process. This is used in conjunction with OAauth/OpenID type authentication schemes
    abstract public function PartialResponse();
   
    // What to do after authentication has succeeded. 
    abstract public function SuccessResponse();
   
    // What to do if the entry/auth/* page is triggered for a user that is already logged in
    abstract public function RepeatResponse();
   
    // What to do if the entry/leave/* page is triggered for a user that is logged in and successfully logs out
    public function LogoutResponse() 
    { 
    }
   
    // What to do if the entry/auth/* page is triggered but login is denied or fails
    public function FailedResponse() 
    { 
    }
   
    // Get one of the three Forwarding URLs (Registration, SignIn, SignOut)
    abstract public function GetURL($URLType);

    public function __construct() 
    {
        // Figure out what the authenticator alias is
        $this->_AuthenticationSchemeAlias = $this->GetAuthenticationSchemeAlias();
      
        // Initialize gdn_pluggable
        parent::__construct();
    }
   
    public function DataSourceType() 
    {
        return $this->_DataSourceType;
    }
   
    public function FetchData($DataSource, $DirectSupplied = array()) 
    {
        $this->_DataSource = $DataSource;
      
        if ($DataSource == $this) {
            foreach ($this->_DataHooks as $DataTarget => $DataHook) {
                $this->_DataHooks[$DataTarget]['value'] = ArrayValue($DataTarget, $DirectSupplied); 
            }
            
            return;
        }
      
        if (sizeof($this->_DataHooks)) {
            foreach ($this->_DataHooks as $DataTarget => $DataHook) {
                switch ($this->_DataSourceType) {
                case self::DATA_REQUEST:
                case self::DATA_FORM:
                    $this->_DataHooks[$DataTarget]['value'] = $this->_DataSource->GetValue($DataHook['lookup'], false);
                    break;
               
                case self::DATA_COOKIE:
                    $this->_DataHooks[$DataTarget]['value'] = $this->_DataSource->GetValueFrom(Gdn_Authenticator::INPUT_COOKIES, $DataHook['lookup'], false);
                    break;
                }
            }
        }
    }
   
    public function HookDataField($InternalFieldName, $DataFieldName, $DataFieldRequired = true) 
    {
        $this->_DataHooks[$InternalFieldName] = array('lookup' => $DataFieldName, 'required' => $DataFieldRequired);
    }

    public function GetValue($Key, $Default = false) 
    {
        if (array_key_exists($Key, $this->_DataHooks) && array_key_exists('value', $this->_DataHooks[$Key])) {
            return $this->_DataHooks[$Key]['value']; 
        }
         
        return $Default;
    }
   
    protected function _CheckHookedFields() 
    {
        foreach ($this->_DataHooks as $DataKey => $DataValue) {
            if ($DataValue['required'] == true && (!array_key_exists('value', $DataValue) || $DataValue['value'] == null)) { return Gdn_Authenticator::MODE_GATHER; 
            }
        }
      
        return Gdn_Authenticator::MODE_VALIDATE;
    }
      
    public function GetProvider($ProviderKey = null, $Force = false) 
    {
        static $AuthModel = null;
        static $Provider = null;
      
        if (is_null($AuthModel)) {
            $AuthModel = new Gdn_AuthenticationProviderModel(); 
        }
      
        $AuthenticationSchemeAlias = $this->GetAuthenticationSchemeAlias();
        if (is_null($Provider) || $Force === true) {
         
            if (!is_null($ProviderKey)) {
                $ProviderData = $AuthModel->GetProviderByKey($ProviderKey);
            } else {
                $ProviderData = $AuthModel->GetProviderByScheme($AuthenticationSchemeAlias, Gdn::Session()->UserID);
                if (!$ProviderData && Gdn::Session()->UserID > 0) {
                    $ProviderData = $AuthModel->GetProviderByScheme($AuthenticationSchemeAlias, null);
                }
            }
         
            if ($ProviderData) {
                $Provider = $ProviderData; 
            }
            else {
                return false; 
            }
        }
      
        return $Provider;
    }
   
    public function GetToken() 
    {
        $Provider = $this->GetProvider();
        if (is_null($this->Token)) {
            $UserID = Gdn::Authenticator()->GetIdentity();
            $UserAuthenticationData = Gdn::SQL()->Select('uat.*')
            ->From('UserAuthenticationToken uat')
            ->Join('UserAuthentication ua', 'ua.ForeignUserKey = uat.ForeignUserKey')
            ->Where('ua.UserID', $UserID)
            ->Where('ua.ProviderKey', $Provider['AuthenticationKey'])
            ->Limit(1)
            ->Get();
            
            if ($UserAuthenticationData->NumRows()) {
                $this->Token = $UserAuthenticationData->FirstRow(DATASET_TYPE_ARRAY); 
            }
            else {
                return false; 
            }
        }
      
        return $this->Token;
    }

    public function GetNonce() 
    {
        $Token = $this->GetToken();
        if (is_null($this->Nonce)) {
            $UserNonceData = Gdn::SQL()->Select('uan.*')
            ->From('UserAuthenticationNonce uan')
            ->Where('uan.Token', $this->Token['Token'])
            ->Get();
            
            if ($UserNonceData->NumRows()) {
                $this->Nonce = $UserNonceData->FirstRow(DATASET_TYPE_ARRAY); 
            }
            else {
                return false; 
            }
        }
      
        return $this->Nonce;
    }
   
    public function CreateToken($TokenType, $ProviderKey, $UserKey = null, $Authorized = false) 
    {
        $TokenKey = implode('.', array('token',$ProviderKey,time(),mt_rand(0, 100000)));
        $TokenSecret = sha1(md5(implode('.', array($TokenKey,mt_rand(0, 100000)))));
        $Timestamp = time();
      
        $Lifetime = Gdn::Config('Garden.Authenticators.handshake.TokenLifetime', 60);
        if ($Lifetime == 0 && $TokenType == 'request') {
            $Lifetime = 300; 
        }
      
        $InsertArray = array(
         'Token' => $TokenKey,
         'TokenSecret' => $TokenSecret,
         'TokenType' => $TokenType,
         'ProviderKey' => $ProviderKey,
         'Lifetime' => $Lifetime,
         'Authorized' => $Authorized,
         'ForeignUserKey' => null
        );
      
        if ($UserKey !== null) {
            $InsertArray['ForeignUserKey'] = $UserKey; 
        }
      
        try {
            Gdn::SQL()
            ->Set('Timestamp', 'NOW()', false)
            ->Insert('UserAuthenticationToken', $InsertArray);
            
            if ($TokenType == 'access' && !is_null($UserKey)) {
                $this->DeleteToken($ProviderKey, $UserKey, 'request'); 
            }
        } catch(Exception $e) {
            return false;
        }
         
        return $InsertArray;
    }
   
    public function AuthorizeToken($TokenKey) 
    {
        try {
            Gdn::Database()->SQL()->Update('UserAuthenticationToken uat')
            ->Set('Authorized', 1)
            ->Where('Token', $TokenKey)
            ->Put();
        } catch (Exception $e) { return false; 
        }
        return true;
    }
   
    public function LookupToken($ProviderKey, $UserKey, $TokenType = null) 
    {
   
        $TokenData = Gdn::Database()->SQL()
         ->Select('uat.*')
         ->From('UserAuthenticationToken uat')
         ->Where('uat.ForeignUserKey', $UserKey)
         ->Where('uat.ProviderKey', $ProviderKey)
         ->BeginWhereGroup()
            ->Where('(uat.Timestamp + uat.Lifetime) >=', 'NOW()')
            ->OrWHere('uat.Lifetime', 0)
         ->EndWhereGroup()
         ->Get()
         ->FirstRow(DATASET_TYPE_ARRAY);
         
        if ($TokenData && (is_null($TokenType) || strtolower($TokenType) == strtolower($TokenData['TokenType']))) {
            return $TokenData; 
        }
      
        return false;
    }
   
    public function DeleteToken($ProviderKey, $UserKey, $TokenType) 
    {
        Gdn::Database()->SQL()
         ->From('UserAuthenticationToken')
         ->Where('ProviderKey', $ProviderKey)
         ->Where('ForeignUserKey', $UserKey)
         ->Where('TokenType', $TokenType)
         ->Delete();
    }
   
    public function SetNonce($TokenKey, $Nonce, $Timestamp = null) 
    {
        $InsertArray = array(
         'Token'     => $TokenKey,
         'Nonce'     => $Nonce,
         'Timestamp' => date('Y-m-d H:i:s', (is_null($Timestamp)) ? time() : $Timestamp)
        );
      
        try {
            $NumAffected = Gdn::Database()->SQL()->Update('UserAuthenticationNonce')
            ->Set('Nonce', $InsertArray['Nonce'])
            ->Set('Timestamp', $InsertArray['Timestamp'])
            ->Where('Token', $InsertArray['Token'])
            ->Put();
         
            if (!$NumAffected || !$NumAffected->PDOStatement() || !$NumAffected->PDOStatement()->rowCount()) {
                throw new Exception("Nothing to update."); 
            }
            
        } catch (Exception $e) {
            $Inserted = Gdn::Database()->SQL()->Insert('UserAuthenticationNonce', $InsertArray);
        }
        return true;
    }
   
    public function LookupNonce($TokenKey, $Nonce = null) 
    {
      
        $NonceData = Gdn::Database()->SQL()->Select('uan.*')
         ->From('UserAuthenticationNonce uan')
         ->Where('uan.Token', $TokenKey)
         ->Get()
         ->FirstRow(DATASET_TYPE_ARRAY);
         
        if ($NonceData && (is_null($Nonce) || $NonceData['Nonce'] == $Nonce)) {
            return $NonceData['Nonce']; 
        }
      
        return false;
    }
   
    public function ClearNonces($TokenKey) 
    {
        Gdn::SQL()->Delete(
            'UserAuthenticationNonce', array(
            'Token'  => $TokenKey
            )
        );
    }
   
    public function RequireLogoutTransientKey() 
    {
        return true;
    }
   
    public function GetAuthenticationSchemeAlias() 
    {
        $StipSuffix = str_replace('Gdn_', '', __CLASS__);
        $ClassName = str_replace('Gdn_', '', get_class($this));
        $ClassName = substr($ClassName, -strlen($StipSuffix)) == $StipSuffix ? substr($ClassName, 0, -strlen($StipSuffix)) : $ClassName;
        return strtolower($ClassName);
    }
   
    public function SetIdentity($UserID, $Persist = true) 
    {
        $AuthenticationSchemeAlias = $this->GetAuthenticationSchemeAlias();
        Gdn::Authenticator()->SetIdentity($UserID, $Persist);
        Gdn::Session()->Start();
      
        if ($UserID > 0) {
            Gdn::Session()->SetPreference('Authenticator', $AuthenticationSchemeAlias);
        } else {
            Gdn::Session()->SetPreference('Authenticator', '');
        }
    }
   
    public function GetProviderValue($Key, $Default = false) 
    {
        $Provider = $this->GetProvider();
        if (array_key_exists($Key, $Provider)) {
            return $Provider[$Key]; 
        }
         
        return $Default;
    }
   
    public function GetProviderKey() 
    {
        return $this->GetProviderValue('AuthenticationKey');
    }
   
    public function GetProviderSecret() 
    {
        return $this->GetProviderValue('AssociationSecret');
    }
   
    public function GetProviderUrl() 
    {
        return $this->GetProviderValue('URL');
    }

}
