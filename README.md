# extension-lti-proctoring
LTI Proctoring extension

The endpoint for this service is:
`https://YOUR_DOMAIN/ltiProctoring/ProctoringTool/launch?delivery=YOUR_DELIVERY_URI`
or
`https://YOUR_DOMAIN/ltiProctoring/ProctoringTool/{"delivery":"YOUR_URI"}(base64 encoded)`
This can be auto-generated for the test taker experience using the LTI button in the deliveries section in the TAO admin user-interface. If using this method you will have to manually update the path to target proctoring.

The expected role of the proctor is:
`urn:lti:role:ims/lis/TeachingAssistant`
