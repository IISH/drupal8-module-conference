<?php
namespace Drupal\iish_conference\API\Domain;

use Drupal\iish_conference\API\ApiCriteriaBuilder;
use Drupal\iish_conference\API\CRUDApiClient;

/**
 * Holds a paper keyword obtained from the API
 */
class PaperKeywordApi extends CRUDApiClient {
  protected $paper_id;
  protected $paper;
  protected $groupName;
  protected $keyword;

  private $paperInstance;

  /**
   * The paper to which the keyword is added
   *
   * @return PaperApi The paper
   */
  public function getPaper() {
    if (!$this->paperInstance) {
      $this->paperInstance = PaperApi::createNewInstance($this->paper);
    }
    return $this->paperInstance;
  }

  /**
   * Set the paper to which the keyword is added
   *
   * @param int|PaperApi $paper The paper (id)
   */
  public function setPaper($paper) {
    if ($paper instanceof PaperApi) {
      $paper = $paper->getId();
    }
    $this->paper = NULL;
    $this->paperInstance = NULL;
    $this->paper_id = $paper;
    $this->toSave['paper.id'] = $paper;
  }

  /**
   * The id of the paper to which the keyword is added
   *
   * @return int The paper id
   */
  public function getPaperId() {
    return $this->paper_id;
  }

  /**
   * Returns this papers group name
   *
   * @return string|null This papers group name
   */
  public function getGroupName() {
    return $this->groupName;
  }

  /**
   * Sets this papers group name
   *
   * @param string|null $groupName This papers group name
   */
  public function setGroupName($groupName) {
    $groupName = (($groupName !== NULL) && strlen(trim($groupName)) > 0) ? trim($groupName) : NULL;

    $this->groupName = $groupName;
    $this->toSave['groupName'] = $groupName;
  }

  /**
   * Returns this papers group name
   *
   * @return string|null This papers group name
   */
  public function getKeyword() {
    return $this->keyword;
  }

  /**
   * Sets this papers keyword
   *
   * @param string|null $keyword This papers keyword
   */
  public function setKeyword($keyword) {
    $keyword = (($keyword !== NULL) && strlen(trim($keyword)) > 0) ? trim($keyword) : NULL;

    $this->keyword = $keyword;
    $this->toSave['keyword'] = $keyword;
  }

  /**
   * Get all keywords for a specific paper in a specific group
   *
   * @param PaperApi|int $paper The paper or paper id
   * @param string $group The name of the group
   *
   * @return PaperKeywordApi[] A list of keywords
   */
  public static function getKeywordsForPaperInGroup($paper, $group) {
    if ($paper instanceof PaperApi) {
      $paper = $paper->getId();
    }

    $props = new ApiCriteriaBuilder();
    $results = PaperKeywordApi::getListWithCriteria(
      $props
        ->eq('paper_id', $paper)
        ->eq('groupName', $group)
        ->get()
    );

    return $results->getResults();
  }
} 