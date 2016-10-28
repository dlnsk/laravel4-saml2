<?php

namespace Pitbulk\Saml2\Controllers;

use Config;
use Event;
use Redirect;
use Saml2Auth;
use Controller;
use Response;
use Log;


class Saml2Controller extends Controller
{

    /**
     * Generate local sp metadata
     * @return \Illuminate\Http\Response
     */
    public function metadata()
    {
        $metadata = Saml2Auth::getMetadata();
        $response = Response::make($metadata, 200);

        $response->header('Content-Type', 'text/xml');

        return $response;
    }

    /**
     * This initiates a login request to the IdP.
     */
    public function login()
    {
        Saml2Auth::login();
        //does not return, it executes a redirection
    }

    /**
     * This initiates a logout request across all the SSO infrastructure.
     */
    public function logout()
    {
        Saml2Auth::logout();
        //does not return, it executes a redirection
    }

    /**
     * Process an incoming saml2 assertion request.
     * Fires 'saml2.loginRequestReceived' event if a valid user is Found
     */
    public function acs()
    {
        Saml2Auth::acs();
        $user = Saml2Auth::getSaml2User();
        Event::fire('saml2.loginRequestReceived', array($user));
        $redirectUrl = $user->getIntendedUrl();

        if($redirectUrl !== null){
            return Redirect::to($redirectUrl);
        } else {
            $samlSettings = Config::get('saml2::saml_settings');
            $loginRoute = $samlSettings['lavarel']['loginRoute'];
            return Redirect::to($loginRoute); //may be set a configurable default
        }
    }

    /**
     * Process an incoming saml2 logout request.
     * Fires 'saml2.logoutRequestReceived' event if its valid.
     * This means the user logged out of the SSO infrastructure, you 'should' log him out locally too.
     */
    public function sls()
    {   $samlSettings = Config::get('saml2::saml_settings');
        $retrieveParametersFromServer = $samlSettings['lavarel']['retrieveParametersFromServer'];

        $errors = Saml2Auth::sls($retrieveParametersFromServer);
        if (!empty($errors)) {
            Log::error("Could not log out", $errors);
            throw new \Exception("Could not log out");
        }
        Event::fire('saml2.logoutRequestReceived');
        $logoutRoute = $samlSettings['lavarel']['logoutRoute'];
        return Redirect::to($logoutRoute); //may be set a configurable default
    }
}
