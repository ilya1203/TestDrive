
class Entity:

    def __init__(self, params):
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
                        "values": field['values']
                    })
                else:
                    exec(f"self.{key} = {fields}")
            else:
                exec(f"self.{key} = {params[key]}")

    def get_custom_field_values(self, _id: int):
        if self.custom_fields_values:
            for field in self.custom_fields_values:
                if field['field_id'] == _id:
                    return field['values']

        return None

    def set_custom_field_values(self, _id: int, *args):
        if self.custom_fields_values:
            for field in self.custom_fields_values:
                if field['field_id'] == _id:
                    field['values'] = []
                    for arg in args:
                        field['values'].append({"value": arg})
                    else:
                        return {"status": "update"}
            else:
                self.custom_fields_values.append(
                    {
                        "field_id": _id,
                        "values": [
                            {"value": arg} for arg in args
                        ]
                    }
                )
                return {'status': "create"}
        else:
            exec(f"self.custom_fields_values = []")
            self.custom_fields_values.append({
                        "field_id": _id,
                        "values": [
                            {"value": arg} for arg in args
                        ]
                    }
            )
            return {"status": "create"}
