<?php
/**
 * Tags functions
 * @author rohith.mohan@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

/**
 * NOTE: DO NOT USE THIS FUNCTION DIRECTLY
 *       Please use the tags proxy functions like api_audio_tags_get()
 *
 * @param string $type
 * @param int $id
 * @param array|string|null $tags
 * @return array|false|mixed
 */
function api_tags_get($type, $id, $tags = null) {
	list($existing_tags, $encrypt_tags) = api_tags_get_existing_tag_details($type, $id);

	if (!$existing_tags) {
		return is_scalar($tags) ? false : [];
	}

	if (is_null($tags)) {
		return api_tags_decrypt_values($existing_tags, $encrypt_tags);
	}

	if (is_array($tags)) {
		$result = [];
		foreach ($tags as $key) {
			if (@array_key_exists($key, $existing_tags)) {
				$result[$key] = $existing_tags[$key];
			}
		}

		return api_tags_decrypt_values($result, $encrypt_tags);
	}

	if (@array_key_exists($tags, $existing_tags)) {
		$decrypted_tags = api_tags_decrypt_values([$tags => $existing_tags[$tags]], $encrypt_tags);
		return $decrypted_tags[$tags];
	}

	return false;
}

/**
 * NOTE: DO NOT USE THIS FUNCTION DIRECTLY
 *       Please use the tags proxy functions like api_audio_tags_set()
 *
 * @param string $type
 * @param int $id
 * @param array $tags
 * @param array $encrypt_tags
 * @return bool
 */
function api_tags_set($type, $id, array $tags = [], array $encrypt_tags = []) {
	// safety check
	$sql = "SELECT `id` FROM `key_store` USE INDEX (`type`) WHERE `type` = ? AND `id` = ? LIMIT 1";
	$rs = api_db_query_read($sql, [$type, $id]);
	if (!$rs || !$rs->RecordCount()) {
		return api_error_raise("Can not set tags for {$type} Item id  = {$id}");
	}

	list($existing_tags, $existing_encrypt_tags) = api_tags_get_existing_tag_details($type, $id);

	$existing_encrypt_tags_updated = false;
	$overridden_tags = array_intersect_key($tags, $existing_tags);
	// Remove those from encryption list whose values are overridden and has not been requested for encryption
	if ($overridden_tags && $existing_encrypt_tags) {
		$previously_encrypted_overridden_tags = array_intersect(array_keys($overridden_tags), $existing_encrypt_tags);

		$tags_to_be_removed_from_existing_encrypt_list = array_diff(
			$previously_encrypted_overridden_tags, $encrypt_tags
		);

		if ($tags_to_be_removed_from_existing_encrypt_list) {
			$existing_encrypt_tags = array_diff($existing_encrypt_tags, $tags_to_be_removed_from_existing_encrypt_list);
			$existing_encrypt_tags_updated = true;
		}
	}

	if (
		($encrypt_tags || $existing_encrypt_tags_updated) &&
		!api_tags_store_encrypt_tags(
			$type,
			$id,
			array_unique(
				array_merge($existing_encrypt_tags, $encrypt_tags)
			)
		)
	) {
		return false;
	}

	foreach ($tags as $key => $value) {
		if ('' !== $key) {
			if (in_array($key, $encrypt_tags)) {
				if (!is_string($value) && !is_numeric($value)) {
					return api_error_raise('Encrypted tag value has to be a string or an integer');
				}
				$existing_tags[$key] = api_misc_crypt_base64($value);
			} else {
				$existing_tags[$key] = $value;
			}
		}
	}

	return api_tags_store_tags($type, $id, $existing_tags);
}

/**
 * NOTE: DO NOT USE THIS FUNCTION DIRECTLY
 *       Please use the tags proxy functions like api_audio_tags_delete()
 *
 * @param string $type
 * @param int $id
 * @param array $tags
 * @return bool
 */
function api_tags_delete($type, $id, array $tags = []) {
	list($existing_tags, $existing_encrypt_tags) = api_tags_get_existing_tag_details($type, $id);

	if (!$existing_tags) {
		return true;
	}

	$new_tags = array_diff_key($existing_tags, array_flip($tags));
	$new_encrypt_tags = array_diff($existing_encrypt_tags, $tags);

	if ($new_tags === $existing_tags) {
		// No change, so don't need to persist
		return true;
	}

	if (api_tags_store_tags($type, $id, $new_tags)) {
		if ($new_encrypt_tags === $existing_encrypt_tags) {
			// No need to persist
			return true;
		}

		return api_tags_store_encrypt_tags($type, $id, $new_encrypt_tags);
	}

	return false;
}

/**
 * Returns all existing campaigns and existing encrypt tags
 * @param string $type
 * @param int $id
 * @param bool $get_decrypted_values
 * @return array
 */
function api_tags_get_existing_tag_details($type, $id, $get_decrypted_values = false) {
	$items = api_keystore_get_multi_byitem($type, $id, ['tags', 'encrypt_tags']);

	$existing_tags = isset($items['tags']) ? unserialize($items['tags']) : [];
	$existing_encrypt_tags = isset($items['encrypt_tags']) ? unserialize($items['encrypt_tags']) : [];

	if ($get_decrypted_values) {
		$existing_tags = api_tags_decrypt_values($existing_tags, $existing_encrypt_tags);
	}

	return [$existing_tags, $existing_encrypt_tags];
}

/**
 * @param string $type
 * @param int $id
 * @param array $tags
 * @return bool
 */
function api_tags_store_tags($type, $id, array $tags) {
	return api_keystore_set($type, $id, 'tags', serialize($tags));
}

/**
 * @param string $type
 * @param int $id
 * @param array $encrypt_tags
 * @return bool
 */
function api_tags_store_encrypt_tags($type, $id, array $encrypt_tags = []) {
	return api_keystore_set($type, $id, 'encrypt_tags', serialize($encrypt_tags));
}

/**
 * @param array $tags
 * @param array $encrypt_tags
 * @return array
 */
function api_tags_decrypt_values(array $tags, array $encrypt_tags = []) {
	if (!$encrypt_tags) {
		return $tags;
	}

	array_walk($tags, function(&$value, $key) use ($encrypt_tags) {
		$value = in_array($key, $encrypt_tags) ? api_misc_decrypt_base64($value) : $value;
	});

	return $tags;
}
