<?php
//Kaltura entry IDs we want to transfer from current kaltura instance
//These video items will get a new ID in the new On-Prem instance
//This old ID will be located in <referenceid>, e.g. mk_1_ip2qfbnw
$FILTERED_IDS = array(
	"1_jaa3ovu5",
	"1_q004i184",
	"1_v337h7ru",
	"1_tlnovp34",
	"1_fpgizztk",
	"1_ryz9in1m",
	"1_0d2os7nc",
	"1_f5buete0"
);
$FILTERED_IDS = array_unique($FILTERED_IDS);
