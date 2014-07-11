<?php

class Rating_View_Helper_GetRatingWidget extends Zend_View_Helper_Abstract
{
    /**
     * Get the rating widget, according to current user.
     *
     * @param Record|array $record If array, contains record type and record id.
     * @param array $display Options to display the widget.
     *
     * @return string Html code from the theme.
     */
    public function getRatingWidget($record, $display = array())
    {
        // Check and get record.
        $record = $this->_checkAndGetRecord($record);
        if (empty($record)) {
            return '';
        }

        $user = current_user();
        $ip = $this->_getRemoteIP();
        $table = get_db()->getTable('Rating');
        $rating = $table->findByRecordAndUserOrIP($record, $user, $ip);

        // Set default display if needed.
        if (empty($display)) {
            $display = is_allowed('Rating_Rating', 'add')
                ? array('result text', 'my rate')
                : array('result');
        }
        // Check rights to rate.
        else {
            if (!is_allowed('Rating_Rating', 'add')) {
                $display = array_diff($display, array('my rate', 'my rate text'));
            }
        }

        $params = array(
            'record' => $record,
            'rating' => $rating,
            'display' => $display,
            // This values are set to avoid multiple queries.
            'average_score' => $table->getAverageScore($record),
            'count_ratings' => $table->getCountRatings($record),
        );

        return $this->view->partial('common/rating.php', $params);
    }

    /**
     * Helper to allow record param to be an object or an array.
     *
     * This is useful to avoid to fetch a record when it's not needed, in
     * particular when it's called from the theme.
     *
     * Recommended forms are object and associative array with 'record_type'
     * and 'record_id' as keys.
     *
     * @return null|Record
     */
    protected function _checkAndGetRecord($record)
    {
        if (is_object($record)) {
            return $record;
        }

        if (is_array($record)) {
            if (isset($record['record_type']) && isset($record['record_type'])) {
                $recordType = $record['record_type'];
                $recordId = $record['record_id'];
            }
            elseif (count($record) == 1) {
                $recordId = reset($record);
                $recordType = key($record);
            }
            elseif (count($record) == 2) {
                $recordType = array_shift($record);
                $recordId = array_shift($record);
            }
            else {
                return null;
            }
            return get_record_by_id($recordType, $recordId);
        }

        return null;
     }

    /**
     * Get remote ip address. This check respects privacy settings.
     *
     * @todo Consolidate this function (see Rating Plugin, Rating model, Ajax controller, getRatingWidget).
     *
     * @return string
     */
    protected function _getRemoteIP()
    {
        $privacy = get_option('rating_privacy');
        if ($privacy == 'anonymous') {
            return '';
        }

        // Check if user is behind nginx.
        $ip = isset($_SERVER['HTTP_X_REAL_IP'])
            ? $_SERVER['HTTP_X_REAL_IP']
            : $_SERVER['REMOTE_ADDR'];

        return $privacy == 'clear'
            ? $ip
            : md5($ip);
    }
}