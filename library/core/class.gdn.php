<?php if (!defined('APPLICATION')) { exit(); 
}

/**
 * Framework superobject
 * 
 * Static object that provides an anchor and namespace for many framework 
 * components, such as Controller, Dispatcher, Config, Database, etc.
 *
 * @author    Mark O'Sullivan <markm@vanillaforums.com>
 * @author    Todd Burry <todd@vanillaforums.com> 
 * @author    Tim Gunter <tim@vanillaforums.com>
 * @copyright 2003 Vanilla Forums, Inc
 * @license   http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package   Garden
 * @since     2.0
 * @static
 */

class Gdn
{

    /// CONSTANTS ///
    const AliasAuthenticator = 'Authenticator';
    const AliasCache = 'Cache';
    const AliasConfig = 'Config';
    const AliasDatabase = 'Database';
    const AliasDatabaseStructure = 'DatabaseStructure';
    const AliasDispatcher = 'Dispatcher';
    const AliasLocale = 'Locale';
    const AliasPermissionModel = 'PermissionModel';
    const AliasRequest = 'Request';
    const AliasRouter = 'Router';
    const AliasSession = 'Session';
    const AliasSlice = 'Slice';
    const AliasSqlDriver = 'SqlDriver';
    const AliasUserMetaModel = 'UserMetaModel';
    const AliasUserModel = 'UserModel';

    const AliasApplicationManager = 'ApplicationManager';
    const AliasPluginManager = 'PluginManager';
    const AliasThemeManager = 'ThemeManager';

    const FactoryInstance = 'Instance';
    const FactoryPrototype = 'Prototype';
    const FactorySingleton = 'Singleton';
    const FactoryRealSingleton = 'RealSingleton';
   
    /// PROPERTIES ///
   
    protected static $_Config = null;
   
    /**
 * @var Gdn_Factory The factory used to create core objects in the application. 
*/
    protected static $_Factory = null;
   
    /**
 * @var boolean Whether or not Gdn::FactoryInstall should overwrite existing objects. 
*/
    protected static $_FactoryOverwrite = true;
   
    protected static $_Locale = null;

    protected static $_Request = null;

    protected static $_PluginManager = null;
   
    protected static $_Session = null;
   
    /// METHODS ///
   
    /**
    * Get the application manager
    *
    * @return Gdn_ApplicationManager
    */
    public static function ApplicationManager() 
    {
        return self::Factory(self::AliasApplicationManager);
    }
   
    /**
 * @return Gdn_Auth 
*/
    public static function Authenticator() 
    {
        return self::Factory(self::AliasAuthenticator);
    }
   
    /**
    * Get the cache object
    *
    * @return Gdn_Cache
    */
    public static function Cache() 
    {
        return self::Factory(self::AliasCache);
    }
   
    /**
    * Get a configuration setting for the application.
    * @param string $Name    The name of the configuration setting. Settings in different sections are seperated by a dot ('.')
    * @param mixed  $Default The result to return if the configuration setting is not found.
    * @return Gdn_Config|mixed The configuration setting.
    */
    public static function Config($Name = false, $Default = false) 
    {
        $Config = self::$_Config;
        if ($Name === false) {
            $Result = $Config; 
        }
        else {
            $Result = $Config->Get($Name, $Default); 
        }
         
        return $Result;
    }

    /**
    * The current controller being targetted.
    * 
    * @param  Gdn_Controller $Value
    * @return Gdn_Controller
    */
    public static function Controller($Value = null) 
    {
        static $Controller = null;

        if ($Value !== null) {
            $Controller = $Value; 
        }

        return $Controller;
    }

    /**
 * Gets the global dispatcher object.
    *
    * @return Gdn_Dispatcher
    */
    public static function Dispatcher() 
    {
        return self::Factory(self::AliasDispatcher);
    }
   
    /**
    * Get a reference to the default database object.
    * @return Gdn_Database
    */
    public static function Database() 
    {
        return self::Factory(self::AliasDatabase);
    }
   
    /**
    * Get an object from the factory.
    * @param string $Alias The alias of the class.
    * @param mixed  $Args  A variable number of arguments to pass to the constructor.
    * @see Gdn_Factory::Factory()
    */
    public static function Factory($Alias = false) 
    {
        if (is_null(self::$_Factory)) {
            self::SetFactory(new Gdn_Factory());
            self::FactoryOverwrite(false);
        }

        if ($Alias === false) {
            return self::$_Factory; 
        }
      
        // Get the arguments to pass to the factory.
        //$Args = array($Arg1, $Arg2, $Arg3, $Arg4, $Arg5);
        $Args = func_get_args();
        array_shift($Args);
        return self::$_Factory->Factory($Alias, $Args);
    }
   
    /**
    * Checks whether or not a factory alias exists.
    * 
    * @param  string $Alias The alias of the factory to check for.
    * @return boolean Whether or not a factory definintion exists.
    * @see    Gdn_Factory::Exists()
    */
    public static function FactoryExists($Alias) 
    {
        return self::Factory()->Exists($Alias);
    }
   
    /**
    * Install a class to the factory.
    * 
    * @param string $Alias       An alias for the class that will be used to retreive instances of it.
    * @param string $ClassName   The actual name of the class.
    * @param string $Path        The path to the class' file. You can prefix the path with ~ to start at the application root.
    * @param string $FactoryType The way objects will be instantiated for the class. One of (Gdn::FactoryInstance, Gdn::FactoryPrototype, Gdn::FactorySingleton).
    * @see   Gdn_Factory::Install()
    */
    public static function FactoryInstall($Alias, $ClassName, $Path = '', $FactoryType = self::FactorySingleton, $Data = null) 
    {
        // Don't overwrite an existing definition.
        if (self::$_FactoryOverwrite === false && self::FactoryExists($Alias)) {
            return; 
        }
      
        self::Factory()->Install($Alias, $ClassName, $Path, $FactoryType, $Data);
      
        // Cache some of the more commonly used factory objects as properties.
        switch ($Alias) {
        case self::AliasConfig:
            self::$_Config = self::Factory($Alias);
            break;
        case self::AliasLocale:
            self::$_Locale = self::Factory($Alias);
            break;
        case self::AliasRequest:
            self::$_Request = self::Factory($Alias);
            break;
        case self::AliasPluginManager:
            self::$_PluginManager = self::Factory($Alias);
            break;
        case self::AliasSession:
            self::$_Session = null;
            break;
        }
    }
   
    /**
    * Installs a dependency to the factory.
    *
    *   @param string $Alias        The alias of the class that will have the dependency.
    * @param string $PropertyName The name of the property on the class that will have the dependency.
    * @param string $SourceAlias  The alias of the class that will provide the value of the property when objects are instantiated.
    * @see   Gdn_Factory::InstalDependency()
    */
    public static function FactoryInstallDependency($Alias, $PropertyName, $SourceAlias) 
    {
        self::Factory()->InstallDependency($Alias, $PropertyName, $SourceAlias);
    }
   
    /**
    * Installs a dependency to the factory with the settings from a configuration.
    *
    * @param mixed  $Config The configuration of the factory definition. This argument can be of the following types: - <b>string</b>: The configuration will be looked up by calling inline{@link Gdn::Config()} - <b>array</b>: The configuration will be set from the array.
    * - <b>string</b>: The configuration will be looked up by calling inline{@link Gdn::Config()}
    * - <b>array</b>: The configuration will be set from the array.
    * @param string $Alias  The class alias to install into the factory. If omitted then it must be in the configuration. The dependency will be installed from the configuration array depending on the following keys: - <b>Alias</b>: Optional if $Alias is passed as an argument. - <b>PropertyName</b>: Required. - <b>SourceAlias</b>: Required. - <b>Override</b> Optional. All of these values are passed to the corresponding argument in inline{@link Gdn::FactoryInstallDependency()}.
    *
    * The dependency will be installed from the configuration array depending on the following keys:
    * - <b>Alias</b>: Optional if $Alias is passed as an argument.
    * - <b>PropertyName</b>: Required.
    * - <b>SourceAlias</b>: Required.
    * - <b>Override</b> Optional.
    * All of these values are passed to the corresponding argument in inline{@link Gdn::FactoryInstallDependency()}.
    */
    public static function FactoryInstallDependencyFromConfig($Config, $Alias = null) 
    {
        if (is_string($Config)) {
            $Config = self::Config($Config); 
        }
        if (is_null($Alias)) {
            $Alias = $Config['Alias']; 
        }
         
        $PropertyName = $Config['PropertyName'];
        $SourceAlias = $Config['SourceAlias'];
        $Override = ArrayValue('Override', $Config, true);
      
        self::FactoryInstallDependency($Alias, $PropertyName, $SourceAlias, $Override);
    }
   
    /**
    * Installs a class to the factory with the settings from a configuration.
    *
    * @param mixed  $Config The configuration of the factory definition. This argument can be of the following types: - <b>string</b>: The configuration will be looked up by calling inline{@link Gdn::Config()} - <b>array</b>: The configuration will be set from the array.
    * - <b>string</b>: The configuration will be looked up by calling inline{@link Gdn::Config()}
    * - <b>array</b>: The configuration will be set from the array.
    * @param string $Alias  The class alias to install into the factory. If omitted then it must be in the configuration. The factory will be installed from the configuration array depending on the following keys: - <b>Alias</b>: Optional if $Alias is passed as an argument. - <b>FactoryType</b>: Required. - <b>Data</b>: Optional. - <b>Override</b> Optional. - <b>Dependencies</b> Optional. Dependencies for the class can be defined as a subarray. Each item in the subarray will be passed to inline{@link Gdn::FactoryInstallDependencyFromConfig}. All of these values (except Dependencies) are passed to the corresponding argument in inline{@link Gdn::FactoryInstall()}.
    *
    * The factory will be installed from the configuration array depending on the following keys:
    * - <b>Alias</b>: Optional if $Alias is passed as an argument.
    * - <b>FactoryType</b>: Required.
    * - <b>Data</b>: Optional.
    * - <b>Override</b> Optional.
    * - <b>Dependencies</b> Optional. Dependencies for the class can be defined as a subarray. Each item in the subarray will be passed to inline{@link Gdn::FactoryInstallDependencyFromConfig}.
    * All of these values (except Dependencies) are passed to the corresponding argument in inline{@link Gdn::FactoryInstall()}.
    */
    public static function FactoryInstallFromConfig($Config, $Alias = null) 
    {
        if (is_string($Config)) {
            $Config = self::Config($Config); 
        }
        if (is_null($Alias)) {
            $Alias = $Config['Alias']; 
        }
         
        $FactoryType = $Config['FactoryType'];
        $Data = ArrayValue('Data', $Config, null);
        $Override = ArrayValue('Override', $Config, true);
         
        self::FactoryInstall($Alias, $Config['ClassName'], $Config['Path'], $FactoryType, $Data, $Override);
      
        if (array_key_exists('Dependencies', $Config)) {
            $Dependencies = $Config['Dependencies'];
            foreach($Dependencies as $Index => $DependencyConfig) {
                self::FactoryInstallFromConfig($DependencyConfig, $Alias);
            }
        }
    }
   
    public static function FactoryOverwrite($Value = null) 
    {
        $Result = (self::$_FactoryOverwrite & 1 > 0);
      
        if (!is_null($Value)) {
            self::$_FactoryOverwrite = $Value;
        }
      
        return $Result;
    }
   
    /**
    * Uninstall an class from the factory.
    * 
    * @param string $Alias The alias of the class to uninstall.
    * @see   Gdn_Factory::Uninstall()
    */
    public static function FactoryUninstall($Alias) 
    {
        self::Factory()->Uninstall($Alias);
    }
   
    /**
    * Uninstall a dependency from the factory.
    *
    * @see Gdn_Factory::UninstallDependency()
    */
    public static function FactoryUninstallDependency($Alias, $PropertyName = null) 
    {
        self::Factory()->UninstallDependency($Alias, $PropertyName);
    }
   
    /**
    * Gets/Sets the Garden InstallationID
    * 
    * @staticvar string $InstallationID
    * @param     string $SetInstallationID
    * @return    string Installation ID or NULL
    */
    public static function InstallationID($SetInstallationID = null) 
    {
        static $InstallationID = false;
        if (!is_null($SetInstallationID)) {
            if ($SetInstallationID !== false) {
                SaveToConfig('Garden.InstallationID', $SetInstallationID);
            } else {
                RemoveFromConfig('Garden.InstallationID');
            }
            $InstallationID = $SetInstallationID;
        }
      
        if ($InstallationID === false) {
            $InstallationID = C('Garden.InstallationID', null); 
        }
      
        return $InstallationID;
    }
   
    /**
    * Gets/Sets the Garden Installation Secret
    * 
    * @staticvar string $InstallationSecret
    * @param     string $SetInstallationSecret
    * @return    string Installation Secret or NULL
    */
    public static function InstallationSecret($SetInstallationSecret = null) 
    {
        static $InstallationSecret = false;
        if (!is_null($SetInstallationSecret)) {
            if ($SetInstallationSecret !== false) {
                SaveToConfig('Garden.InstallationSecret', $SetInstallationSecret);
            } else {
                RemoveFromConfig('Garden.InstallationSecret');
            }
            $InstallationSecret = $SetInstallationSecret;
        }
      
        if ($InstallationSecret === false) {
            $InstallationSecret = C('Garden.InstallationSecret', null); 
        }
      
        return $InstallationSecret;
    }
   
    /**
    * @return Gdn_Locale
    */
    public static function Locale() 
    {
        if (is_null(self::$_Locale)) {
            self::$_Locale = self::Factory(self::AliasLocale); 
        }
      
        return self::$_Locale;
    }
   
    /**
    * Get the permission model for the application.
    *
    * @return PermissionModel
    */
    public static function PermissionModel() 
    {
        return self::Factory(self::AliasPermissionModel);
    }

    /**
    * Get the plugin manager for the application.
    *
    * @return Gdn_PluginManager
    */
    public static function PluginManager() 
    {
        return self::$_PluginManager; //self::Factory(self::AliasPluginManager);
    }
   
    public static function Regarding() 
    {
        return self::Factory('Regarding');
    }
   
    /**
    * Get or set the current request object.
    * @param Gdn_Request $NewRequest The new request or null to just get the request.
    * @return Gdn_Request
    */
    public static function Request($NewRequest = null) 
    {
        $Request = self::$_Request; //self::Factory(self::AliasRequest);
        if (!is_null($NewRequest)) {
            if (is_string($NewRequest)) {
                $Request->WithURI($NewRequest); 
            }
            elseif (is_object($NewRequest))
            $Request->FromImport($NewRequest);
        }
      
        return $Request;
    }
   
    /**
    * Get the router object
    *
    * @return Gdn_Router
    */
    public static function Router() 
    {
        return self::Factory(self::AliasRouter);
    }
   
    /**
    * Get the session object.
    *
    * @return Gdn_Session
    */
    public static function Session() 
    {
        if (is_null(self::$_Session)) {
            self::$_Session = self::Factory(self::AliasSession); 
        }
        return self::$_Session;
    }
   
    /**
    * Get a reference to the Gdn_Slice
    * 
    * @param  string $Slice Slice to execute
    * @return Gdn_Slice
    */
    public static function Slice($Slice) 
    {
        $Result = self::Factory(self::AliasSlice);
        return $Result->Execute($Slice);
    }
   
    public static function Set($Key, $Value = null) 
    {
        return Gdn::UserMetaModel()->SetUserMeta(0, $Key, $Value);
    }
   
    public static function Get($Key, $Default = null) 
    {
        $Response = Gdn::UserMetaModel()->GetUserMeta(0, $Key, $Default);
        if (sizeof($Response) == 1) {
            return GetValue($Key, $Response, $Default); 
        }
        return $Default;
    }
   
    /**
    * Get a reference to the default SQL driver object.
    * 
    * @return Gdn_SQLDriver
    * @see    Gdn_Database::SQL()
    */
    public static function SQL() 
    {
        $Database = self::Database();
        $Result = $Database->SQL();
        return $Result;
    }
   
    /**
    * Get a reference to the Statistics object.
    * @return Gdn_Statistics
    */
    public static function Statistics() 
    {
        return self::Factory('Statistics');
    }
   
    /**
    * Get a reference to the default database structure object.
    * @return Gdn_DatabaseStructure
    */
    public static function Structure() 
    {
        $Database = self::Database();
        $Result = $Database->Structure();
        return $Result;
    }
   
    /**
    * Get the plugin manager for the application.
    *
    * @return Gdn_ThemeManager
    */
    public static function ThemeManager() 
    {
        return self::Factory(self::AliasThemeManager);
    }
   
    /**
    * Translates a code into the selected locale's definition.
    *
    * @param  string $Code    The code related to the language-specific definition.
    * @param  string $Default The default value to be displayed if the translation code is not found.
    * @return string The translated string or $Code if there is no value in $Default.
    */
    public static function Translate($Code, $Default = false) 
    {
        $Locale = Gdn::Locale();
        if ($Locale) {
            return $Locale->Translate($Code, $Default); 
        }
        else {
            return $Default; 
        }
    }
   
    /**
    * Get a reference to the user model.
    * 
    * @return UserModel
    */
    public static function UserModel() 
    {
        return self::Factory(self::AliasUserModel);
    }
   
    /**
    * Get a reference to the user meta model.
    * 
    * @return UserMetaModel
    */
    public static function UserMetaModel() 
    {
        static $UserMetaModel = null;
        if (is_null($UserMetaModel)) {
            $UserMetaModel = new UserMetaModel(); 
        }
        return $UserMetaModel;
    }
   
    /**
    * Set the object used as the factory for the api.
    * 
    * @param Gdn_Factory $Factory  The object used as the factory.
    * @param boolean     $Override whether to override the property if it is already set.
    */
    public static function SetFactory($Factory, $Override = true) 
    {
        if ($Override || is_null(self::$_Factory)) {
            self::$_Factory = $Factory; 
        }
    }
}