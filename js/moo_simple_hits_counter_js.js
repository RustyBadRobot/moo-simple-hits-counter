// send request to server to save visitor/view after 1 second of page load
jQuery(function () {
	// console.log(document.cookie);
	jQuery.ajax({
		type:'POST',
		data:{action:'moo_update_counter'},
		url: templateUrl+"/wp-admin/admin-ajax.php?post_id="+post_id,
		success: function(value) {
		}
	});
});