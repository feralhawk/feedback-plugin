<?php
/**
 * Created by PhpStorm.
 * User: Constantin Purcaru
 * Date: 03/09/2017
 * Time: 14:13
 */

namespace Feedback\DataProviders;


use Feedback\Helpers\FeedbackCoreHelper;
use Feedback\Services\FeedbackService;
use Plenty\Modules\Feedback\Contracts\FeedbackAverageRepositoryContract;
use Plenty\Modules\Feedback\Contracts\FeedbackRepositoryContract;
use Plenty\Modules\Feedback\Controllers\FeedbackController;
use Plenty\Modules\Frontend\Services\AccountService;
use Plenty\Plugin\Http\Request;
use Plenty\Plugin\Templates\Twig;

class Feedback
{

    /**
     * @param Request $request
     * @param Twig $twig
     * @param FeedbackService $feedbackService
     * @param FeedbackCoreHelper $coreHelper
     * @param FeedbackRepositoryContract $feedbackRepository
     * @param FeedbackAverageRepositoryContract $feedbackAverageRepository
     * @param AccountService $accountService
     * @return string
     */
    public function call(Request $request, Twig $twig, FeedbackService $feedbackService, FeedbackCoreHelper $coreHelper, FeedbackRepositoryContract $feedbackRepository, FeedbackAverageRepositoryContract $feedbackAverageRepository, AccountService $accountService)
    {

        $targetId = 1;

        // Details about the user currently authenticated
        $authenticatedContact = [
            'id' => $accountService->getAccountContactId(),
            'check' => $accountService->getIsAccountLoggedIn()
        ];

        $options['timestampVisibility'] = $coreHelper->configValue(FeedbackCoreHelper::KEY_TIMESTAMP_VISIBILITY) == 'true' ? true : false;

        $average = $feedbackAverageRepository->getFeedbackAverage($targetId);


        if(empty($average)){

            // Default values if the average table doesn't have any entry for this item/variation
            $counts = [
                'ratingsCountOf1' => 0,
                'ratingsCountOf2' => 0,
                'ratingsCountOf3' => 0,
                'ratingsCountOf4' => 0,
                'ratingsCountOf5' => 0,
                'ratingsCountTotal' => 0,
                'averageValue' => 0,
                'highestCount' => 0
            ];

        }else{

            $counts = [
                'ratingsCountOf1' => $average->ratingsCountOf1,
                'ratingsCountOf2' => $average->ratingsCountOf2,
                'ratingsCountOf3' => $average->ratingsCountOf3,
                'ratingsCountOf4' => $average->ratingsCountOf4,
                'ratingsCountOf5' => $average->ratingsCountOf5
            ];

            $highestCount = max($counts);

            $counts['ratingsCountTotal'] = $average->ratingsCountTotal;
            $counts['averageValue'] = $average->averageValue;
            $counts['highestCount'] = $highestCount;

        }





        if($authenticatedContact['check']){

            // Pagination settings for currently authenticated user's feedbacks
            $page = $request->get('page');
            $page = isset($page) && $page != 0 ? $page : 1;
            $itemsPerPage = 50;
            $with = [];
            $filters = [
                'targetId' => $targetId,
                'sourceId' => $authenticatedContact['id']
            ];

            // List of currently authenticated user's feedbacks
            $feedbacks = $feedbackService->listFeedbacks($feedbackRepository, $page, $itemsPerPage, $with, $filters);
            $results = $feedbacks->getResult()->all();

            $authenticatedContact['limitReached'] = $coreHelper->configValue(FeedbackCoreHelper::KEY_MAXIMUM_NR_FEEDBACKS) <= $feedbacks->getTotalCount() ? true : false;

            $data['feedbacks'] = $results;

        }

        $data['options'] = $options;
        $data['counts'] = $counts;
        $data['authenticatedContact'] = $authenticatedContact;

        return $twig->render('Feedback::DataProvider.Feedback', $data);
    }
}