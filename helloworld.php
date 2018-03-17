<link rel='stylesheet' href='https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css' integrity='sha384-1q8mTJOASx8j1Au+a5WDVnPi2lkFfwwEAa8hDDdjZlpLegxhjVME1fgjWPGmkzs7' crossorigin='anonymous'>
<link rel='stylesheet' href='https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css' integrity='sha384-1q8mTJOASx8j1Au+a5WDVnPi2lkFfwwEAa8hDDdjZlpLegxhjVME1fgjWPGmkzs7' crossorigin='anonymous'>
<link rel='stylesheet' href='//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css'>
<script src='//code.jquery.com/jquery-1.10.2.js'></script>
<script src='//code.jquery.com/ui/1.11.4/jquery-ui.js'></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js" integrity="sha384-0mSbJDEHialfmuBBQP6A4Qrprq5OVfW37PRR3j5ELqxss1yVqOtnepnHVP9aJ7xS" crossorigin="anonymous"></script>

<?php
use google\appengine\api\users\User;
use google\appengine\api\users\UserService;
// Enter the path that the oauth library is in relation to the php file
require_once('OAuth.php');
require_once('Purl.php');

$user = UserService::getCurrentUser();
if (isset($user)) 
{
  echo sprintf('Welcome, %s! (<a href="%s">sign out</a>)',
               $user->getNickname(),
               UserService::createLogoutUrl('/'));

    if (!empty($_POST)){ ?>
        <div class='container-fluid'>
          <div class='well'>
           <div class='row'>
            <div class='col-md-8 col-md-offset-2'>
                <h2>We help you find the best <?php echo htmlspecialchars($_POST['term']); ?>, near <?php echo htmlspecialchars($_POST['location']); ?>.</h2>
                <?php
                    /**
                     * Yelp API v2.0 code sample.
                     *
                     * This program demonstrates the capability of the Yelp API version 2.0
                     * by using the Search API to query for businesses by a search term and location,
                     * and the Business API to query additional information about the top result
                     * from the search query.
                     * 
                     * Please refer to http://www.yelp.com/developers/documentation for the API documentation.
                     * 
                     * This program requires a PHP OAuth2 library, which is included in this branch and can be
                     * found here:
                     *      http://oauth.googlecode.com/svn/code/php/
                     * 
                     * Sample usage of the program:
                     * `php sample.php --term="bars" --location="San Francisco, CA"`
                     */

                    // Set your OAuth credentials here  
                    // These credentials can be obtained from the 'Manage API Access' page in the
                    // developers documentation (http://www.yelp.com/developers)

                    $CONSUMER_KEY = 'cq8CwlaxWJlmxf3E1kLUmg';
                    $CONSUMER_SECRET = 'C_YthFnOkExx4xZHG4PU52F820U';
                    $TOKEN = 'z7_Yghh2PHtZ00zbeVZAEM4_bKcwN315';
                    $TOKEN_SECRET = 'eTZb7PkNeFiAq_5eIMuBuEiFJRg';
                    $API_HOST = 'api.yelp.com';
                    $DEFAULT_TERM = 'food';
                    $DEFAULT_LOCATION = 'Cincinnati, OH';
                    $SEARCH_LIMIT = 5;
                    $SEARCH_PATH = '/v2/search/';
                    $BUSINESS_PATH = '/v2/business/';

                    /** 
                     * Makes a request to the Yelp API and returns the response
                     * 
                     * @param    $host    The domain host of the API 
                     * @param    $path    The path of the APi after the domain
                     * @return   The JSON response from the request      
                     */
                    function request($host, $path) {
                        $unsigned_url = "https://" . $host . $path;

                        // Token object built using the OAuth library
                        $token = new OAuthToken($GLOBALS['TOKEN'], $GLOBALS['TOKEN_SECRET']);

                        // Consumer object built using the OAuth library
                        $consumer = new OAuthConsumer($GLOBALS['CONSUMER_KEY'], $GLOBALS['CONSUMER_SECRET']);

                        // Yelp uses HMAC SHA1 encoding
                        $signature_method = new OAuthSignatureMethod_HMAC_SHA1();

                        $oauthrequest = OAuthRequest::from_consumer_and_token(
                            $consumer, 
                            $token, 
                            'GET', 
                            $unsigned_url
                        );
                        
                        // Sign the request
                        $oauthrequest->sign_request($signature_method, $consumer, $token);
                        
                        // Get the signed URL
                        $signed_url = $oauthrequest->to_url();
                        
                        // Send Yelp API Call
                        try {
                            $ch = curl_init($signed_url);
                            if (FALSE === $ch)
                                throw new Exception('Failed to initialize');
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                            curl_setopt($ch, CURLOPT_HEADER, 0);
                            $data = curl_exec($ch);

                            if (FALSE === $data)
                                throw new Exception(curl_error($ch), curl_errno($ch));
                            $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                            if (200 != $http_status)
                                throw new Exception($data, $http_status);

                            curl_close($ch);
                        } catch(Exception $e) {
                            trigger_error(sprintf(
                                'Curl failed with error #%d: %s',
                                $e->getCode(), $e->getMessage()),
                                E_USER_ERROR);
                        }
                        
                        return $data;
                    }
                    /**
                     * Query the Search API by a search term and location 
                     * 
                     * @param    $term        The search term passed to the API 
                     * @param    $location    The search location passed to the API 
                     * @return   The JSON response from the request 
                     */
                    function search($term, $location) {
                        $url_params = array();
                        
                        $url_params['term'] = $term ?: $GLOBALS['DEFAULT_TERM'];
                        $url_params['location'] = $location?: $GLOBALS['DEFAULT_LOCATION'];
                        $url_params['limit'] = $GLOBALS['SEARCH_LIMIT'];
                        $search_path = $GLOBALS['SEARCH_PATH'] . "?" . http_build_query($url_params);
                        
                        return request($GLOBALS['API_HOST'], $search_path);
                    }
                    /**
                     * Query the Business API by business_id
                     * 
                     * @param    $business_id    The ID of the business to query
                     * @return   The JSON response from the request 
                     */
                    function get_business($business_id) {
                        $business_path = $GLOBALS['BUSINESS_PATH'] . $business_id;
                        
                        return request($GLOBALS['API_HOST'], $business_path);
                    }
                    /**
                     * Queries the API by the input values from the user 
                     * 
                     * @param    $term        The search term to query
                     * @param    $location    The location of the business to query
                     */
                    function query_api($term, $location) {  
                        $response = json_decode(search($term, $location));
                        for($i=0;$i<=4;$i++)
                        {
                            $business_id[$i]= $response->businesses[$i]->id;
                        }
                        ?>
                        <table style="width:100%">
                        <?php
                        for($i=0;$i<=4;$i++)
                        {
                            $response = get_business($business_id[$i]);
                            $json=json_decode($response,true);
                            ?>
                              <tr>
                                <td><?php echo $json['name'] ?></td>
                                <td><?php echo $json['rating'] ?></td>
                                <td><?php echo $json['location']['city'] ?></td>
                                <td><a href=<?php echo $json['url'] ?>> More Info </a></td> 
                              </tr>
                        <?php
                        }
                        ?>
                        </table>
                        <?php
                    }
                    query_api($_POST['term'], $_POST['location']);
    }else{?>
        <div class="container-fluid">
         <div class="well">
          <div class="row">
           <div class="col-md-6 col-md-offset-3">
            <form action=<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?> method="post">
             <h2>Find local businesses</h2>
             <input class="form-control" type="text" name="term" placeholder="Find the best of what you want." required="required"><br>
             <select name="location" size="10" style="width: 557px;height: 173px;margin-bottom: 10;">
                <option value="Alaska">Alaska</option>
                <option value="Arizona">Arizona</option>
                <option value="Arkansas">Arkansas</option>
                <option value="California">California</option>
                <option value="Colorado">Colorado</option>
                <option value="Connecticut">Connecticut</option>
                <option value="Delaware">Delaware</option>
                <option value="District Of Columbia"> Columbia</option>
                <option value="Florida">Florida</option>
                <option value="Georgia">Georgia</option>
                <option value="Hawaii">Hawaii</option>
                <option value="Idaho">Idaho</option>
                <option value="Illinois">Illinois</option>
                <option value="Indiana">Indiana</option>
                <option value="Iowa">Iowa</option>
                <option value="Kansas">Kansas</option>
                <option value="Kentucky">Kentucky</option>
                <option value="Louisiana">Louisiana</option>
                <option value="Maine">Maine</option>
                <option value="Maryland">Maryland</option>
                <option value="Massachusetts">Massachusetts</option>
                <option value="Michigan">Michigan</option>
                <option value="Minnesota">Minnesota</option>
                <option value="Mississippi">Mississippi</option>
                <option value="Missouri">Missouri</option>
                <option value="Montana">Montana</option>
                <option value="Nebraska">Nebraska</option>
                <option value="Nevada">Nevada</option>
                <option value="New Hampshire">New Hampshire</option>
                <option value="New Jersey">New Jersey</option>
                <option value="New Mexico">New Mexico</option>
                <option value="New York">New York</option>
                <option value="North Carolina">North Carolina</option>
                <option value="North Dakota">North Dakota</option>
                <option value="Ohio">Ohio</option>
                <option value="Oklahoma">Oklahoma</option>
                <option value="Oregon">Oregon</option>
                <option value="Pennsylvania">Pennsylvania</option>
                <option value="Rhode Island">Rhode Island</option>
                <option value="South Carolina">South Carolina</option>
                <option value="South Dakota">South Dakota</option>
                <option value="Tennessee">Tennessee</option>
                <option value="Texas">Texas</option>
                <option value="Utah">Utah</option>
                <option value="Vermont">Vermont</option>
                <option value="Virginia">Virginia</option>
                <option value="Washington">Washington</option>
                <option value="West Virginia">West Virginia</option>
                <option value="Wisconsin">Wisconsin</option>
                <option value="Wyoming">Wyoming</option>
            </select>
            <br>
            <button type="submit" class="btn btn-primary" name="submit">Search</button>
            </form>
           </div>
          </div>
         </div>
        </div>
        <?php
    }
}else{?>
    <div class='container-fluid'>
        <div class='well'>
            <div class='row'>
                <div class="col-md-4 col-md-offset-5">
                    <?php
                        echo sprintf('<a href="%s">Sign in or register</a> ',
                        UserService::createLoginUrl('/'));
                    ?>
                </div>
            </div>
            <div class='row'>
                <div class="col-md-6 col-md-offset-3">
                    <h1>Find the best of what you want!<br></h1>
                </div>  
            </div>
        </div>
    </div>
<?php
}
?>
