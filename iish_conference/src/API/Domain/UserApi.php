<?php
namespace Drupal\iish_conference\API\Domain;

use Drupal\iish_conference\API\CRUDApiMisc;
use Drupal\iish_conference\API\CRUDApiClient;
use Drupal\iish_conference\API\MailNewPasswordApi;
use Drupal\iish_conference\API\CachedConferenceApi;
use Drupal\iish_conference\API\LoggedInUserDetails;
use Drupal\iish_conference\API\SettingsApi;

/**
 * Holds a user obtained from the API
 */
class UserApi extends CRUDApiClient {
  protected $email;
  protected $lastName;
  protected $firstName;
  protected $gender;
  protected $title;
  protected $address;
  protected $city;
  protected $country_id;
  protected $phone;
  protected $fax;
  protected $mobile;
  protected $organisation;
  protected $department;
  protected $education;
  protected $cv;
  protected $extraInfo;
  protected $dietaryWishes;
  protected $otherDietaryWishes;
  protected $optIn;
  protected $papers_id;
  protected $daysPresent_day_id;
  protected $addedBy_id;

  private $sessionParticipants;
  private $papers;
  private $country;
  private $days;
  private $addedBy;
  private $reviews;

  /**
   * Allows the creation of a user via an array with details
   *
   * @param array $user An array with user details
   *
   * @return UserApi A user object
   */
  public static function getUserFromArray(array $user) {
    return self::createNewInstance($user);
  }

  /**
   * Returns the address of this user
   *
   * @return string|null The address
   */
  public function getAddress() {
    return $this->address;
  }

  /**
   * Set the address of this user
   *
   * @param string|null $address The address
   */
  public function setAddress($address) {
    $address = (($address !== NULL) && strlen(trim($address)) > 0) ? trim($address) : NULL;

    $this->address = $address;
    $this->toSave['address'] = $address;
  }

  /**
   * Returns the city of this user
   *
   * @return string|null The city
   */
  public function getCity() {
    return $this->city;
  }

  /**
   * Set the city of this user
   *
   * @param string|null $city The city
   */
  public function setCity($city) {
    $city = (($city !== NULL) && strlen(trim($city)) > 0) ? trim($city) : NULL;

    $this->city = $city;
    $this->toSave['city'] = $city;
  }

  /**
   * Returns the CV of this user
   *
   * @return string|null The CV
   */
  public function getCv() {
    return $this->cv;
  }

  /**
   * Set the CV of this user
   *
   * @param string|null $cv The CV
   */
  public function setCv($cv) {
    $cv = (($cv !== NULL) && strlen(trim($cv)) > 0) ? trim($cv) : NULL;

    $this->cv = $cv;
    $this->toSave['cv'] = $cv;
  }

  /**
   * Returns the email address of this user
   *
   * @return string The email address
   */
  public function getEmail() {
    return $this->email;
  }

  /**
   * Set the email of this user
   *
   * @param string|null $email The email address
   */
  public function setEmail($email) {
    $email = (($email !== NULL) && strlen(trim($email)) > 0) ? trim($email) : NULL;

    $this->email = $email;
    $this->toSave['email'] = $email;
  }

  /**
   * Returns extra information added by this user
   *
   * @return string|null Extra information added by this user
   */
  public function getExtraInfo() {
    return $this->extraInfo;
  }

  /**
   * Returns the fax of this user
   *
   * @return string|null The fex
   */
  public function getFax() {
    return $this->fax;
  }

  /**
   * Set the fax of this user
   *
   * @param string|null $fax The fax number
   */
  public function setFax($fax) {
    $fax = (($fax !== NULL) && strlen(trim($fax)) > 0) ? trim($fax) : NULL;

    $this->fax = $fax;
    $this->toSave['fax'] = $fax;
  }

  /**
   * Returns the gender of this user ('M' or 'F')
   *
   * @return string|null The gender
   */
  public function getGender() {
    return $this->gender;
  }

  /**
   * Set the gender of this user
   *
   * @param string|null $gender The gender
   */
  public function setGender($gender) {
    $gender = (($gender !== NULL) && strlen(trim($gender)) > 0) ? trim($gender) : NULL;

    $this->gender = $gender;
    $this->toSave['gender'] = $gender;
  }

  /**
   * Returns the mobile number of this user
   *
   * @return string|null The mobile number of this user
   */
  public function getMobile() {
    return $this->mobile;
  }

  /**
   * Set the mobile of this user
   *
   * @param string|null $mobile The mobile number
   */
  public function setMobile($mobile) {
    $mobile = (($mobile !== NULL) && strlen(trim($mobile)) > 0) ? trim($mobile) : NULL;

    $this->mobile = $mobile;
    $this->toSave['mobile'] = $mobile;
  }

  /**
   * Returns a list with ids of all papers of this user
   *
   * @return int[] Paper ids of this user
   */
  public function getPapersId() {
    return $this->papers_id;
  }

  /**
   * Returns a list with all papers of this user
   *
   * @return PaperApi[] The papers of this user
   */
  public function getPapers() {
    if (!$this->papers) {
      $this->papers =
        CRUDApiMisc::getAllWherePropertyEquals(new PaperApi(), 'user_id', $this->getId())
          ->getResults();
    }

    return $this->papers;
  }

  /**
   * Returns the phone number of this user
   *
   * @return string|null The phone number
   */
  public function getPhone() {
    return $this->phone;
  }

  /**
   * Set the phone of this user
   *
   * @param string|null $phone The phone number
   */
  public function setPhone($phone) {
    $phone = (($phone !== NULL) && strlen(trim($phone)) > 0) ? trim($phone) : NULL;

    $this->phone = $phone;
    $this->toSave['phone'] = $phone;
  }

  /**
   * Returns the title of this user (Dr., Prof. etc.)
   *
   * @return string|null The title
   */
  public function getTitle() {
    return $this->title;
  }

  /**
   * Set the title of this user
   *
   * @param string|null $title The title
   */
  public function setTitle($title) {
    $title = (($title !== NULL) && strlen(trim($title)) > 0) ? trim($title) : NULL;

    $this->title = $title;
    $this->toSave['title'] = $title;
  }

  /**
   * Returns the location details of the user (department, organisation and country)
   *
   * @return string A comma seperated string of department, organisation and country
   */
  public function getLocationDetails() {
    $locations = array();
    if (SettingsApi::getSetting(SettingsApi::SHOW_DEPARTMENT, 'bool') &&
      ($this->getDepartment() !== NULL) && (strlen($this->getDepartment()) > 0)) {
      $locations[] = $this->getDepartment();
    }
    if (($this->getOrganisation() !== NULL) && (strlen($this->getOrganisation()) > 0)) {
      $locations[] = $this->getOrganisation();
    }
    if ($this->getCountry() !== NULL) {
      $locations[] = $this->getCountry()->__toString();
    }

    return implode(', ', $locations);
  }

  /**
   * Returns the department of this user
   *
   * @return string|null The department
   */
  public function getDepartment() {
    return $this->department;
  }

  /**
   * Set the department of this user
   *
   * @param string|null $department The department
   */
  public function setDepartment($department) {
    $department = (($department !== NULL) && strlen(trim($department)) > 0) ? trim($department) : NULL;

    $this->department = $department;
    $this->toSave['department'] = $department;
  }

  /**
   * Returns the education of this user
   *
   * @return string|null The education
   */
  public function getEducation() {
    return $this->education;
  }

  /**
   * Set the education of this user
   *
   * @param string|null $education The education
   */
  public function setEducation($education) {
    $education = (($education !== NULL) && strlen(trim($education)) > 0) ? trim($education) : NULL;

    $this->education = $education;
    $this->toSave['education'] = $education;
  }

  /**
   * Returns the organisation of this user
   *
   * @return string|null The organisation of this user
   */
  public function getOrganisation() {
    return $this->organisation;
  }

  /**
   * Set the organisation of this user
   *
   * @param string|null $organisation The organisation
   */
  public function setOrganisation($organisation) {
    $organisation = (($organisation !== NULL) && strlen(trim($organisation)) > 0) ? trim($organisation) : NULL;

    $this->organisation = $organisation;
    $this->toSave['organisation'] = $organisation;
  }

  /**
   * Returns the country of this user
   *
   * @return CountryApi|null The country
   */
  public function getCountry() {
    if (!$this->country) {
      $countries = CachedConferenceApi::getCountries();

      foreach ($countries as $country) {
        if ($country->getId() == $this->getCountryId()) {
          $this->country = $country;
          break;
        }
      }
    }

    return $this->country;
  }

  /**
   * Set the country of this user
   *
   * @param int|CountryApi $country The country (id)
   */
  public function setCountry($country) {
    if ($country instanceof CountryApi) {
      $country = $country->getId();
    }

    $this->country = NULL;
    $this->country_id = $country;
    $this->toSave['country.id'] = $country;
  }

  /**
   * Returns the id of the country of this user
   *
   * @return int|null The country id
   */
  public function getCountryId() {
    return $this->country_id;
  }

  /**
   * Returns combined session participants information of this user
   *
   * @return CombinedSessionParticipantApi[] The session participant information
   */
  public function getCombinedSessionParticipantInfo() {
    if (!$this->sessionParticipants) {
      $this->sessionParticipants =
        CRUDApiMisc::getAllWherePropertyEquals(new CombinedSessionParticipantApi(), 'user_id', $this->getId())
          ->getResults();
    }

    return $this->sessionParticipants;
  }

  /**
   * Returns the ids of the days this user is present
   *
   * @return int[] The day ids
   */
  public function getDaysPresentDayId() {
    return $this->daysPresent_day_id;
  }

  /**
   * Set the days for which this participant signed up to be present
   *
   * @param int[]|DayApi[] $days The days (or their ids) to add to this participant
   */
  public function setDaysPresent($days) {
    $this->days = NULL;
    $this->daysPresent_day_id = array();

    foreach ($days as $day) {
      if ($day instanceof DayApi) {
        $this->daysPresent_day_id[] = $day->getId();
      }
      else {
        if (is_int($day)) {
          $this->daysPresent_day_id[] = $day;
        }
      }
    }

    $this->toSave['daysPresent.day.id'] = implode(';', $this->daysPresent_day_id);
  }

  /**
   * Returns the days this user is present
   *
   * @return DayApi[] The days
   */
  public function getDaysPresent() {
    if (!$this->days) {
      $this->days = array();

      if (is_array($this->daysPresent_day_id)) {
        foreach ($this->daysPresent_day_id as $dayId) {
          foreach (CachedConferenceApi::getDays() as $day) {
            if ($day->getId() === $dayId) {
              $this->days[] = $day;
            }
          }
        }
      }
    }

    return $this->days;
  }

  /**
   * Returns the user that created this user
   *
   * @return UserApi The user that created this user
   */
  public function getAddedBy() {
    if (!$this->addedBy && is_int($this->getAddedById())) {
      $this->addedBy = CRUDApiMisc::getById(new UserApi(), 'id', $this->getAddedById());
    }

    return $this->addedBy;
  }

  /**
   * Set the user who added this user
   *
   * @param int|UserApi $addedBy The user (id)
   */
  public function setAddedBy($addedBy) {
    if ($addedBy instanceof UserApi) {
      $addedBy = $addedBy->getId();
    }

    $this->addedBy = NULL;
    $this->addedBy_id = $addedBy;
    $this->toSave['addedBy.id'] = $addedBy;
  }

  /**
   * The user id of the user who created this user
   *
   * @return int The user id of the user who created this user
   */
  public function getAddedById() {
    return $this->addedBy_id;
  }

  public function save() {
    // Before we save it... we need to know whether this is a new user
    $isUpdate = $this->isUpdate();

    $save = parent::save();

    // Make sure to invalidate the cached user
    if ($save) {
      LoggedInUserDetails::invalidateUser();
    }

    // If it is a new user, mail him his new password
    if (!$isUpdate && $save) {
      $mailNewPasswordApi = new MailNewPasswordApi();
      $mailNewPasswordApi->mailNewPassword($this);
    }

    return $save;
  }

  public function __toString() {
    return $this->getFullName();
  }

  /**
   * Returns the full name of this user
   *
   * @return string The full name
   */
  public function getFullName() {
    return trim($this->getFirstName()) . ' ' . trim($this->getLastName());
  }

  /**
   * Compare two users, by last name, then by first name
   *
   * @param UserApi $instance Compare this instance with the given instance
   *
   * @return int &lt; 0 if <i>$instA</i> is less than
   * <i>$instB</i>; &gt; 0 if <i>$instA</i>
   * is greater than <i>$instB</i>, and 0 if they are
   * equal.
   */
  protected function compareWith($instance) {
    $lastNameCmp = strcmp(strtolower($this->getLastName()), strtolower($instance->getLastName()));
    if ($lastNameCmp === 0) {
      return strcmp(strtolower($this->getFirstName()), strtolower($instance->getFirstName()));
    }
    else {
      return $lastNameCmp;
    }
  }

  /**
   * Returns the last name of this user
   *
   * @return string The last name
   */
  public function getLastName() {
    return $this->lastName;
  }

  /**
   * Set the last name of this user
   *
   * @param string|null $lastName The last name
   */
  public function setLastName($lastName) {
    $lastName = (($lastName !== NULL) && strlen(trim($lastName)) > 0) ? trim($lastName) : NULL;

    $this->lastName = $lastName;
    $this->toSave['lastName'] = $lastName;
  }

  /**
   * Returns the first name of this user
   *
   * @return string The first name
   */
  public function getFirstName() {
    return $this->firstName;
  }

  /**
   * Set the first name of this user
   *
   * @param string|null $firstName The first name
   */
  public function setFirstName($firstName) {
    $firstName = (($firstName !== NULL) && strlen(trim($firstName)) > 0) ? trim($firstName) : NULL;

    $this->firstName = $firstName;
    $this->toSave['firstName'] = $firstName;
  }

  /**
   * Returns the participant details for the current event date of this user.
   *
   * @return ParticipantDateApi The participant.
   */
  public function getParticipantDate() {
    return CRUDApiMisc::getFirstWherePropertyEquals(
      new ParticipantDateApi(), 'user_id', $this->getId());
  }

  /**
   * Returns the reviews by this reviewer
   *
   * @return PaperReviewApi[] The reviews by this reviewer
   */
  public function getReviews() {
    if (!$this->reviews) {
      $this->reviews =
        CRUDApiMisc::getAllWherePropertyEquals(new PaperReviewApi(), 'reviewer_id', $this->getId())
          ->getResults();
    }

    return $this->reviews;
  }

    /**
     * Returns dietary wishes of this user
     *
     * @return int The dietary wishes of this user
     */
    public function getDietaryWishes() {
        return $this->dietaryWishes;
    }

    /**
     * Sets the dietary wishes of this user
     *
     * @param int $dietaryWishes The dietary wishes of this user
     */
    public function setDietaryWishes($dietaryWishes) {
        $this->dietaryWishes = $dietaryWishes;
        $this->toSave['dietaryWishes'] = $dietaryWishes;
    }

    /**
     * Returns the other dietary wishes of this user
     *
     * @return string The other dietary wishes of this user
     */
    public function getOtherDietaryWishes() {
        return $this->otherDietaryWishes;
    }

    /**
     * Sets the other dietary wishes of this user
     *
     * @param mixed $otherDietaryWishes The other dietary wishes of this user
     */
    public function setOtherDietaryWishes($otherDietaryWishes) {
        $otherDietaryWishes = (($otherDietaryWishes !== NULL) && strlen(trim($otherDietaryWishes)) > 0) ? trim($otherDietaryWishes) : NULL;

        $this->otherDietaryWishes = $otherDietaryWishes;
        $this->toSave['otherDietaryWishes'] = $otherDietaryWishes;
    }

    /**
     * Did this user opt in for the newsletter?
     *
     * @return bool Whether this user opt in for the newsletter
     */
    public function getOptIn() {
        return $this->optIn;
    }

    /**
     * Sets whether this user has opt in for the newsletter
     *
     * @param bool $optIn Whethert this user opt in for the newsletter
     */
    public function setOptIn($optIn) {
        $this->optIn = (bool) $optIn;
        $this->toSave['optIn'] = $this->optIn;
    }
}
