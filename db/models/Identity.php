<?php

class Identity extends Model {
  
  function Identity() {
    
    // identity is a Vcard/Hcard-compatible person profile
    
    // fields
    
    $this->char_field( 'label' );
    
    $this->char_field( 'url' );
    
    $this->char_field( 'post_notice' );
    $this->char_field( 'update_profile' );
    $this->char_field( 'token_url' );
    
    $this->char_field( 'family_name' );
    $this->char_field( 'given_name' );
    $this->char_field( 'additional_name' );
    $this->char_field( 'honorific_prefix' );
    $this->char_field( 'honorific_suffix' );
    $this->char_field( 'nickname' );
    $this->char_field( 'password' );
    $this->char_field( 'title' );
    $this->char_field( 'role' );
    $this->char_field( 'organization_name' );
    $this->char_field( 'organization_unit' );

    $this->file_field( 'photo' );
    $this->file_field( 'logo' );
    
    $this->char_field( 'token' );
    
    $this->char_field( 'email_type' );
    $this->char_field( 'email_value' );
    $this->char_field( 'tel_type' );
    $this->char_field( 'tel_value' );
    $this->char_field( 'post_office_box' );
    $this->char_field( 'extended_address' );
    $this->char_field( 'street_address' );
    $this->char_field( 'locality' );
    $this->char_field( 'region' );
    $this->char_field( 'postal_code' );
    $this->char_field( 'country_name' );
    $this->char_field( 'adr_type' );
    $this->char_field( 'adr_value' );
    $this->char_field( 'latitude' );
    $this->char_field( 'longitude' );
    $this->char_field( 'tz' );
    $this->char_field( 'dob' );
    $this->char_field( 'gender' );
    $this->char_field( 'language' );
    $this->char_field( 'uid' );
    $this->char_field( 'rev' );
    $this->char_field( 'fn' );
    $this->char_field( 'sort_string' );
    
    $this->bool_field( 'is_primary', true );
    
    $this->int_field( 'entry_id' );
    $this->int_field( 'person_id' );
    
    $this->auto_field( 'id' );
    
    // relationships
    
    $this->has_one( 'entry' );
    $this->has_one( 'person' );
    
    // requirements
    
    $this->validates_presence_of( 'label' );
    
    $this->validates_uniqueness_of( 'url' );
    
    // permissions
    
    $this->let_access( 'all:administrators' );
    
    $this->set_hidden();
    
    
    //$email = "someone@somewhere.com";
    //$default = "http://www.somewhere.com/homestar.jpg";
    //$size = 40;
    
    //You can construct your gravatar url with the following php code:
    
    //$grav_url = "http://www.gravatar.com/avatar.php?
    //gravatar_id=".md5($email).
    //"&amp;default=".urlencode($default).
    //"&amp;size=".$size;
    
    //Once the gravatar URL is created, you can output it whenever you please:
    
    //<img src="... echo $grav_url; ..." alt="" />
    
  }
  
}

?>