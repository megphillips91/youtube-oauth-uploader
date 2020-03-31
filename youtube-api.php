<?php
/**
 * YouTube_Oauth_VSG_Lessons Api Class and any helper functions
 * handles the oAuth, lesson setup, and privacy restrictions, sharing with users
 * this file should contain all the api calls to the Youtube API service
 */
namespace YouTube_Oauth_VSG_Lessons;
use \DateTime;
use \DateTimeZone;
use \DateInterval;
use \Google_Client;


add_shortcode('test_youtube_api', __NAMESPACE__.'\\test_youtube_api');

function test_youtube_api(){
    $youtube = new Youtube_Oauth();
    $url = 'https://www.googleapis.com/upload/youtube/v3/videos';
        $snippet = array(
            "description" => "Noah caught a skate at Hatteras' Cape Point on Saturday.",
            "title" => "Noah's First Skate. Beach Fishing."
            );
        $snippet = (object) $snippet;
        $file = file_get_contents(plugin_dir_path( __FILE__ )."preview_videos/noahs-fish.mp4");
        $body = array(
            'snippet' => $snippet,
            'privacyStatus'=> 'public',
            'video'=>$file,
            'access_token'=>$youtube->token
            );
        $headers = array(
            "Accept" => "application/json",
            "Content-Type" => "application/json"
        );
        $args = array(
            'body'=>$body,
            'headers' => $headers
        );
        $youtube->video_insert_response = json_decode(wp_remote_retrieve_body(wp_remote_post($url, $args)));
    
    echo '<pre>'; var_dump($youtube); echo '</pre>';
    
    /*if(isset($_POST['video_nonce']) && wp_verify_nonce($_POST['video_nonce'], 'upload_vsg_video')){
        if($_FILES['preview_file']) {
            $youtube = new Youtube_video($_FILES['preview_file'], 'public', null);
            $youtube->handle_upload();
            $youtube->post_video_to_youtube('public', NULL);
            echo '<pre>'; var_dump($youtube); echo '</pre>';
        } else {
            trigger_error('Video ismissing from $_FILE', E_USER_ERROR);
        }
    } else {
        $content = '<form action="https://msp-media.org/list-a-small-group-bible-study/" 
            method="post" enctype="multipart/form-data" >
            <input type="text" id="study_name" name="study_name" placeholder="Study Name"/>
            <input type="file" id="preview_file" name="preview_file" placeholder="preview" />
        '.wp_nonce_field('upload_vsg_video', 'video_nonce').'
        <input type="submit" name="submit" />
        </form>';
        return $content;    
    }
    */
        
}

class Youtube_Video extends Youtube_Oauth {
    public $video_file;
    public $privacy;
    public $users;
    public $video_location;
    public $file_saved_to_server;

    public function __construct($file, $privacy, $users){
        parent::__construct();
        $this->initiate($file, $privacy, $users);
    }

    public function initiate($file, $privacy, $users){
        if(!$this->token_is_valid){
            echo '<pre>'; var_dump($this); echo '</pre>';
            trigger_error("Your Token is not valid", E_USER_ERROR);
        } else {
            $this->video_file = $file;
            $this->privacy = $privacy;
            $this->users = $users;
        }
    }

    public function handle_upload(){
            $file_name = $this->video_file['name'];
            $file_size = $this->video_file['size'];
            $file_tmp = $this->video_file['tmp_name'];
            $file_type= $this->video_file['type'];
            $var = explode('.',$file_name);
            $file_ext = strtolower(end($var));
            $this->video_location = plugin_dir_path( __FILE__ )."preview_videos/".$this->video_file['name'];
            if(move_uploaded_file(
                $file_tmp,
                $this->video_location
            )){$this->file_saved_to_server = true; };
        
    }

    public function post_video_to_youtube($privacy, $users){
        $url = 'https://www.googleapis.com/upload/youtube/v3/videos';
        $snippet = array(
            "description" => "Noah caught a skate at Hatteras' Cape Point on Saturday.",
            "title" => "Noah's First Skate. Beach Fishing."
            );
        $snippet = (object) $snippet;
        $file = file_get_contents($this->video_location);
        $body = array(
            'mine'=>'true',
            'snippet' => $snippet,
            'privacyStatus'=> 'public',
            'video'=>$file,
            'access_token'=>$this->token
            );
        $headers = array(
            "Authorization" => "Bearer '.$this->token.'",
            "Accept" => "application/json",
            "Content-Type" => "application/json"
        );
        $args = array(
            'body'=>$body,
            'headers' => $headers
        );
        $this->video_insert_response = json_decode(wp_remote_retrieve_body(wp_remote_post($url, $args)));
    
    }


 }

/**
 * Youtube Oauth Class
 * all calls to the data api must be through the oauth consent.
 * So first verify through oauth then gain access to the vide api
 * The actual API business will be classes that extend this class
 */

 class Youtube_Oauth {
    public $authorize_url;
    public $button;
    public $token;
    public $refresh_token;
    public $token_is_valid;
    public $token_is_expired;
    public $token_expiration;
    public $status;
    protected $client_secret;
    protected $client_id;

    public function __construct( ){
        $this->redirect = 'https://msp-media.org/list-a-small-group-bible-study/';
        $this->client_id = '544237225236-t775bnbsssoptnrv2abdg6klh19crq2h.apps.googleusercontent.com';
        $this->client_secret = 'd5nqJWHQbkDgTZsHARYHUr2_';
        $this->scope = 'https://www.googleapis.com/auth/youtube.upload';
        $this->authorize_url = 'https://accounts.google.com/o/oauth2/v2/auth';
        $this->set_button();
        $this->button =  '<a class="button" href="'.$this->button_href.'">Authorize Our App</a>';
        $this->set_status();
    }

    private function set_status(){
        $this->get_token();
        $this->get_expiration();
        $this->get_refresh_token();
        $this->set_token_is_valid();
        $this->set_state();
    }

    public function set_state(){
        if($this->token_is_valid) {
            $this->status = 'authorized'; //do nothing
            return $this->status;
        } 
        if($this->token_is_expired) {
            if($this->refresh_token){
                $this->refresh_token();
            } else {
                $this->state = 'needs_to_authorize';
                return $this->button;
            }
        }
        if(isset($_GET['state']) && $_GET['state'] == 'video_scope_code' && isset($_GET['code'])){
            $this->set_token($_GET['code']);
        }
    }

   

     /**
      * Setters
      * the following methods go out to google to set oauth tokens, codes, refresh
      */
      public function set_button(){
        $url = $this->authorize_url;
        $params = array(
            'scope' => 'https://www.googleapis.com/auth/youtube.upload',
            'access_type' => 'offline',
            'include_granted_scopes' =>'true',
            'response_type' => 'code',
            'state' => 'video_scope_code',
            'redirect_uri' => $this->redirect,
            'client_id' => $this->client_id
        );
       $this->button_href = \add_query_arg(
            $params,
            $url
        );
     }

     public function refresh_token(){
        $url = 'https://oauth2.googleapis.com/token';
        $body = array(
            'grant_type'=>'refresh_token',
            'refresh_token'=>$this->refresh_token,
            'redirect_uri'=>'https://msp-media.org/list-a-small-group-bible-study/',
            'client_id'=>$this->client_id,
            'client_secret' => $this->client_secret
        );
        $headers = array(
            'Content-Type'=>'application/x-www-form-urlencoded'
        );
        $args = array(
            'body'=>$body,
            'headers' => $headers
        );
        $this->token_response = json_decode(wp_remote_retrieve_body(wp_remote_post($url, $args)));
        $this->update_user_meta();
     }

      public function set_token($code){
        $url = 'https://oauth2.googleapis.com/token';
        $body = array(
            'grant_type'=>'authorization_code',
            'code'=>$code,
            'redirect_uri'=>'https://msp-media.org/list-a-small-group-bible-study/',
            'client_id'=>$this->client_id,
            'client_secret' => $this->client_secret
        );
        $headers = array(
            //"Authorization"=> 'Basic '.$this->basic,
            'Content-Type'=>'application/x-www-form-urlencoded'
        );
        $args = array(
            'body'=>$body,
            'headers' => $headers
        );
        $this->token_response = json_decode(wp_remote_retrieve_body(wp_remote_post($url, $args)));
        $this->update_user_meta();
     }

     private function update_user_meta(){
        update_user_meta(get_current_user_id(), 'vsg-youtube-token', $this->token_response->access_token);
        $this->set_expiration();
        if(isset($this->token_response->refresh_token)){
            update_user_meta(get_current_user_id(), 'vsg-youtube-refresh', $this->token_response->refresh_token);
        }
    }

    private function set_expiration(){
        $expiration = new DateTime(NULL, new DateTimeZone(get_option('timezone_string')));
        $expiration->add(new DateInterval('PT'.$this->token_response->expires_in.'S'));
        update_user_meta(get_current_user_id(), 'vsg-youtube-token-expiration', $expiration->format('Y-m-d H:i:s'));
    }

     /**
      * Getters for youtube token values
      * the following check usermeta for the token, refrsh token, and expiration of the current token
      */
    private function get_expiration(){
        $this->token_expiration =  get_user_meta(
            get_current_user_ID(),
            'vsg-youtube-token-expiration',
            true
        );
    }

    private function get_refresh_token(){
        $this->refresh_token = get_user_meta(
            get_current_user_id(),
            'vsg-youtube-refresh',
            true
        );
    }

    private function get_token(){
        $this->token = get_user_meta(
            get_current_user_id(),
            'vsg-youtube-token',
            true
        );
    }

    public function set_token_is_valid(){
        $now = new DateTime(NULL, new DateTimeZone(get_option('timezone_string')));
        $expiration = new DateTime($this->token_expiration, new DateTimeZone(get_option('timezone_string')));
        if($now < $expiration){
            $this->token_is_valid = true;
            $this->token_is_expired = false;
        } else {
            $this->token_is_valid = false;
            $this->token_is_expired = true;
        }
     }
    
 } // end class youtube oauth



?>