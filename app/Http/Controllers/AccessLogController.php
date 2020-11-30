<?php

namespace App\Http\Controllers;

use App\Services\AccessLogStorageService;
use App\Services\ProcessLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AccessLogController extends Controller
{
    private AccessLogStorageService $fileStorageService;
    private ProcessLogService $processLogService;

    public function __construct(AccessLogStorageService $fss, ProcessLogService $pls)
    {
        $this->fileStorageService = $fss;
        $this->processLogService = $pls;
    }

    public function index()
    {
        # code...
    }

    /**
     * Upload and process access log 
     * 
     * @param Request $request
     * @return string JSON Response
     */
    public function store(Request $request)
    {
        /**
         * Validation rules
         */
        $rules = [
            'name' => 'required|string',
            'file' => 'required|mimetypes:application/gzip,txt|max:100000'
        ];

        /**
         * Custom validation messages
         */
        $messages = [
            'name.required' => 'Missing “name” POST argument',
            'file.mimetypes' => 'The file is not gzipped or txt',
            'file.max' => 'File is > 100Mb'
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        /**
         * Return "bad request" on validation failed
         */
        if($validator->fails()) {
            return response()->json([
                'error_msg' => $validator->errors()->first()
            ], 400);
        }

        $fileName = $request->file('file')->getClientOriginalName();

        /**
         * Check for file name duplicate
         */
        if($this->fileStorageService->isFilePresent($fileName)) {
            return response()->json([
                'error_msg' => "File with name: \"{$fileName}\" already exists"
            ], 400);
        }

        /**
         * Store access log file in filesystem
         */
        $storedFilePath = $this->fileStorageService->store($request->file('file'));
        
        /**
         * Storing failed
         */
        if( $storedFilePath === false) {
            return response()->json([
                'error_msg' => 'Unable to store access log file'
            ], 500);
        }

        /**
         * Parse uploaded access log file and persist parsed data
         */
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

    public function destroy()
    {
        # code...
    }

    public function show()
    {
        # code...
    }

    public function aggregateByIp()
    {
        # code...
    }

    public function aggregateByMethod()
    {
        # code...
    }

    public function aggregateByUrl()
    {
        # code...
    }
}
