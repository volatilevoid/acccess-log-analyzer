<?php

namespace App\Http\Controllers;

use App\Services\AccessLogStorageService;
use App\Services\AccessLogDataService;
use App\Services\ProcessLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AccessLogController extends Controller
{
    private AccessLogStorageService $fileStorageService;
    private AccessLogDataService $logDataService;
    private ProcessLogService $processLogService;

    public function __construct(AccessLogStorageService $fss, ProcessLogService $pls, AccessLogDataService $lds)
    {
        $this->fileStorageService = $fss;
        $this->logDataService = $lds;
        $this->processLogService = $pls;
    }

    /**
     * List of all logs
     *
     * @return void
     */
    public function index()
    {
        return $this->logDataService->allLogs();
    }

    /**
     * Upload and process access log file
     * 
     * @param Request $request
     * @return string JSON Response
     */
    public function store(Request $request)
    {
        // Validation rules
        $rules = [
            'name' => 'required|string',
            'file' => 'required|mimetypes:application/gzip,text/plain|max:100000'
        ];

        // Custom validation messages
        $messages = [
            'name.required' => 'Missing “name” POST argument',
            'file.mimetypes' => 'The file is not gzipped or txt',
            'file.max' => 'File is > 100Mb'
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        // Return "bad request" on validation failed
        if($validator->fails()) {
            return response()->json([
                'error_msg' => $validator->errors()->first()
            ], 400);
        }

        $fileName = $request->file('file')->getClientOriginalName();

        // Check for file name duplicate
        if($this->fileStorageService->isFilePresent($fileName)) {
            return response()->json([
                'error_msg' => "File with name: \"{$fileName}\" already exists"
            ], 400);
        }

        // Check if log name is available
        if(!$this->logDataService->isNameAvailable($request->name)) {
            return response()->json([
                'error_msg' => "Name: \"{$request->name}\" already taken."
            ], 400);
        }

        // Store access log file in filesystem
        $storedFilePath = $this->fileStorageService->store($request->file('file'));
        
        // Storing failed
        if( $storedFilePath === false) {
            return response()->json([
                'error_msg' => 'Unable to store access log file'
            ], 500);
        }

        // Parse uploaded access log file and persist parsed data
        $status = $this->processLogService->handle(
            $fileName, 
            $request->name
        );

        if(is_string($status)) {
            return response()->json([
                'error_msg' => $status
            ], 500);
        }

        return response()->json($status, 200);
    }

    public function destroy(string $name)
    {
        $validator = Validator::make([
            'name' => $name
        ], [
            'name' => 'required|string'
        ]);

        if($validator->fails()) {
            return response()->json([
                'error_msg' => $validator->errors()->first()
            ], 500);  
        }
        
        $fileName = $this->logDataService->getFilename($name);

        if($this->fileStorageService->delete($fileName) === false) {
            // TODO problem deleting or file missing
        }

    }

    public function download()
    {
        # code...
    }

    public function aggregateBy(Request $request, string $byCondition)
    {
        // Aggregate by route parameter possible
        $availableAggregations = ['ip', 'url', 'method'];
        if(!in_array($byCondition, $availableAggregations, true)) {
            return response()->json([
                'error_msg' => 'Group by "' . $byCondition . '" is not available'
            ], 400);
        }
        // Validation rules
        $rules = [
            'dt_start' => 'string|date_format:Y-m-d H:i:s',
            'dt_end' => 'string|date_format:Y-m-d H:i:s'
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            return response()->json([
                'error_msg' => $validator->errors()->first()
            ], 400);
        }

        $dateTimeStart = isset($request->dt_start) ? $request->dt_start : '';
        $dateTimeEnd = isset($request->dt_end) ? $request->dt_end : '';

        return $this->logDataService->getAggregate($byCondition, $dateTimeStart, $dateTimeEnd);
    }
}
