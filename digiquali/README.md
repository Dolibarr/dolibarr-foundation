--- DigiQuali ---
DigiQuali is used to managed control quality by the Dolibarr foundation.
This README contains specific information and tips around DigiQuali.

--- Sql request to extract a control with label of question, answers and tasks to do.
SELECT
	t.fk_question_group,
	ldq2.ref,
	t.fk_question,
	ldq.ref,
	ldq.description,
	t.rowid,
	t.ref,
	t.tms,
	t.answer,
	t.comment,
	t.status,
	count(dt.rowid) as tasks
FROM
	llx_digiquali_controldet as t
LEFT JOIN llx_digiquali_answer as da ON
	da.position = t.answer
	AND da.fk_question = t.fk_question
LEFT JOIN llx_element_element as dt ON
	dt.fk_source = t.rowid
	AND dt.sourcetype = 'controldet'
	And dt.targettype = 'project_task'
INNER JOIN llx_digiquali_question as ldq ON t.fk_question = ldq.rowid 
INNER JOIN llx_digiquali_questiongroup as ldq2 ON t.fk_question_group = ldq2.rowid 
WHERE
	1 = 1
GROUP BY
	t.fk_control,
	t.fk_question_group,
	t.fk_question,
	t.rowid,
	t.ref,
	t.ref_ext,
	t.date_creation,
	t.tms,
	t.type,
	t.answer,
	t.answer_photo,
	t.comment,
	t.fk_user_creat,
	t.fk_user_modif,
	t.status,
	t.import_key,
	da.value
ORDER BY
	t.rowid ASC
LIMIT 21
