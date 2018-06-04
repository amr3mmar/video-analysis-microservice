<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Google\Cloud\VideoIntelligence\V1\VideoIntelligenceServiceClient;
use Google\Cloud\VideoIntelligence\V1\Feature;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
    /**
     * Finds labels in the video.
     *
     * @param string $uri The cloud storage object to analyze. Must be formatted
     *                    like gs://bucketname/objectname
     */

    function analyzeLabels(Request $request)
    {
        # Instantiate a client.
        $video = new VideoIntelligenceServiceClient();

        # $path = '/home/amr3mmar/Desktop/videoplayback.mp4';
        $path = $request->get('path');

        # Read the local video file
        $inputContent = file_get_contents($path);

        # Execute a request.
        $operation = $video->annotateVideo([
            'inputContent' => $inputContent,
            'features' => [Feature::LABEL_DETECTION]
        ]);


        
        # Wait for the request to complete.
        $operation->pollUntilComplete();

        # Print the results.
        if ($operation->operationSucceeded()) {
            $results = $operation->getResult()->getAnnotationResults()[0];

            //return response()->json(["results: "=> $results], 200);
            

            # Process video/segment level label annotations
            foreach ($results->getSegmentLabelAnnotations() as $label) {
                error_log('Video label description: %s' .  $label->getEntity()->getDescription());
                foreach ($label->getCategoryEntities() as $categoryEntity) {
                    error_log('  Category: %s' . $categoryEntity->getDescription());
                }
                foreach ($label->getSegments() as $segment) {
                    $startTimeOffset = $segment->getSegment()->getStartTimeOffset();
                    $startSeconds = $startTimeOffset->getSeconds();
                    $startNanoseconds = floatval($startTimeOffset->getNanos())/1000000000.00;
                    $startTime = $startSeconds + $startNanoseconds;
                    $endTimeOffset = $segment->getSegment()->getEndTimeOffset();
                    $endSeconds = $endTimeOffset->getSeconds();
                    $endNanoseconds = floatval($endTimeOffset->getNanos())/1000000000.00;
                    $endTime = $endSeconds + $endNanoseconds;
                    printf('  Segment: %ss to %ss' . PHP_EOL, $startTime, $endTime);
                    printf('  Confidence: %f' . PHP_EOL, $segment->getConfidence());
                }
            }
            print(PHP_EOL);

            # Process shot level label annotations
            foreach ($results->getShotLabelAnnotations() as $label) {
                printf('Shot label description: %s' . PHP_EOL, $label->getEntity()->getDescription());
                foreach ($label->getCategoryEntities() as $categoryEntity) {
                    printf('  Category: %s' . PHP_EOL, $categoryEntity->getDescription());
                }
                foreach ($label->getSegments() as $shot) {
                    $startTimeOffset = $shot->getSegment()->getStartTimeOffset();
                    $startSeconds = $startTimeOffset->getSeconds();
                    $startNanoseconds = floatval($startTimeOffset->getNanos())/1000000000.00;
                    $startTime = $startSeconds + $startNanoseconds;
                    $endTimeOffset = $shot->getSegment()->getEndTimeOffset();
                    $endSecondseconds = $endTimeOffset->getSeconds();
                    $endNanos = floatval($endTimeOffset->getNanos())/1000000000.00;
                    $endTime = $endSeconds + $endNanoseconds;
                    printf('  Shot: %ss to %ss' . PHP_EOL, $startTime, $endTime);
                    printf('  Confidence: %f' . PHP_EOL, $shot->getConfidence());
                }
            }
            return response()->json(["results: "=> $results], 200);
            print(PHP_EOL);
        } else {
            print_r($operation->getError());
            return $operation->getError();
        }
    }
}
