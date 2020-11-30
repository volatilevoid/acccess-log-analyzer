# Simple access log analyzing app

## Application story

As a server administrator, I would like to have the ability to aggregate web server access logs,so I can detect problems easier.Because access logs can contain a large number of entries, I would like to upload my data tothe server using REST API and then run aggregation operations. The expected size of the fileswill be a max 100Mb. As a result, I would like to get back JSON so I can connect to myReact-based application. 

Log line example: 

 `122.148.162.36 - - [26/Nov/2020:11:06:48 +0000] "GET /click?trvid=10004&trvx=e970dafb&var1=19287402013795679&var2=22928674 HTTP/2.0" 302 177 "-" "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.198 Safari/537.36" "kesimon.com" 0.008 0.008`

Log line format:

`client_ip client_identity(or "-") user(or "-") date_and_time_of_request request_type+resorce_being_requested HTTP_response_status_code size_of_object_returned_to_client request_resource_origin(or "-") user_agent`
## Routes

Route | Method | Description
------|--------|------------
/ | GET | List of available uploaded logs
/log | POST | Upload log file (plain txt or gzipped)
/log/[name] | DELETE | Delete uploaded log
/log/[name] | GET | Download uploaded log
/aggregate/ip | GET | Aggregated by IP
/aggregate/method | GET | Aggregated by HTTP method
/aggregate/url | GET | Aggregate by URL (without GET arguments)

Aggregate routes need to support optional “**dt_start**” and “**dt_end**” arguments that contain thestart and end time on which aggregations will referee, the app will consider only log linesbetween that data range. 

Datetime will be in the format: “**YYYY-MM-DD HH:MM:SS**”

---

## Warning

Make sure that server is configured to accept content larger than 100MB. The configuration is done in php.ini (or .htaccess) by setting the proper values of the following variables:

- upload_max_filesize
- post_max_size

