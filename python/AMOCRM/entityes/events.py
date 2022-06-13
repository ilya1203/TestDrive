import requests as req
import AMOCRM


class Event:

    def __init__(self, params, **kwargs):
        if "token" in kwargs.keys():
            self._token = kwargs['token']
        continue_status = ['loss_reason_id', 'created_by', 'updated_by', 'created_at', 'closed_at'
                           'is_deleted', 'closest_task_at', "group_id",  "updated_at",
                           "account_id", "_links"]
        for key in params.keys():
            if not params[key] or key in continue_status:
                continue
            if isinstance(params[key], str):
                exec(f"self.{key} = '{params[key]}'")
            elif key == "custom_fields_values":
                fields = []
                for field in params[key]:
                    fields.append({
                        "field_id": field['field_id'],
                        "field_name": field['field_name'],
                        "values": field['values']
                    })
                else:
                    exec(f"self.{key} = {fields}")
            else:
                exec(f"self.{key} = {params[key]}")


