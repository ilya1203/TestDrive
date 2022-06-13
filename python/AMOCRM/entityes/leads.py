import AMOCRM


class Lead:

    def __init__(self, params, **kwargs):
        if "token" in kwargs.keys():
            self._token = kwargs['token']
        continue_status = ['loss_reason_id', 'created_by', 'updated_by', 'created_at', 'closed_at'
                           'is_deleted', 'closest_task_at', "group_id",  "updated_at",
                           "account_id", "_links"]
        f_c = "'"
        s_c = '"'
        for key in params.keys():
            if not params[key] or key in continue_status:
                continue
            if isinstance(params[key], str):
                exec(f"self.{key} = '{params[key].replace(f_c, '').replace(s_c, '')}'")
            elif key == "custom_fields_values":
                fields = []
                for field in params[key]:
                    try:
                        for i in range(len(field['values'])):
                            # print(type(field['values'][i]))
                            field['values'][i].pop('enum_id')
                            field['values'][i].pop('enum_code')
                    except:
                        pass
                    fields.append({
                        "field_id": field['field_id'],
                        "field_name": field['field_name'],
                        "values": field['values']
                    })
                else:
                    exec(f"self.{key} = {fields}")
            else:
                exec(f"self.{key} = {params[key]}")

    def __str__(self):
        return f"{self.name} - {self.id}"

    def get_custom_field_values(self, _id):
        if "custom_fields_values" not in vars(self).keys():
            return None
        if isinstance(_id, int):
            if self.custom_fields_values:
                for field in self.custom_fields_values:
                    if field['field_id'] == _id:
                        return field['values']
        elif isinstance(_id, str):
            if self.custom_fields_values:
                for field in self.custom_fields_values:
                    if field['field_name'] == _id:
                        return field['values']
        return None

    def add_tag(self, name: str):
        if "_embedded" in vars(self).keys():
            if "tags" in vars(self._embedded).keys():
                self._embedded['tags'].append({"name": name})
            else:
                self._embedded['tags'] = {"name": name}
        else:
            exec("self._embedded = {'tags': []}")
            self._embedded['tags'] = {"name": name}

    def get_tags(self):
        if "_embedded" in vars(self).keys():
            if "tags" in self._embedded.keys():
                return self._embedded['tags']
            else:
                raise Exception("No tags")
        else:
            raise Exception("No _ebedded")

    def set_custom_field_values(self, _id: int, more: bool = False, *args):
        if "custom_fields_values" in vars(self).keys():
            for field in self.custom_fields_values:
                if field['field_id'] == _id:
                    if not more:
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

    def save(self):
        data_lead = vars(self)
        amo = AMOCRM.AMO(self._token)
        data_lead.pop("_token")
        if 'source_id' in data_lead.keys():
            data_lead.pop('source_id')
        if '_embedded' in data_lead.keys():
            data_lead.pop('_embedded')
        # print(data_lead)
        return amo.change_lead(data_lead)
