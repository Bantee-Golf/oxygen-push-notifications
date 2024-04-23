# Change Log

## v1.1

- For improve privacy, `device_id`, `device_type`, `device_push_token` moved to header params as `x-device-id`, `x-device-push-token`, `x-device-id`
- These parameters used to be on QUERY, so change them to be accepted as header parameters.