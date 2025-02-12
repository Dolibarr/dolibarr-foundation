function doAdminAjax(data, success_func, error_func)
{
	$.ajax(
	{
		url : 'adminajax',
		data : data,
		type : 'POST',
		success : function(data){
			if (success_func)
				return success_func(data);

			data = $.parseJSON(data);
			if (data.confirmations != null)
				showSuccessMessage(data.confirmations);
			else
				showErrorMessage(data.error);
		},
		error : function(data){
			if (error_func)
				return error_func(data);

			alert("[TECHNICAL ERROR]");
		}
	});
}

//display a success/error/notice message
function showSuccessMessage(msg) {
	$.growl.notice({ title: "", message:msg});
}

function showErrorMessage(msg) {
	$.growl.error({ title: "", message:msg});
}