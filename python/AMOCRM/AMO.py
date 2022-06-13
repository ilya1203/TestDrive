import requests as req
import json
from .entityes import *
from time import sleep, time
from .defunctions import uuqo

class AMO:

    def __init__(self, token):

        self.token = token
        self.subdomain = "matveev11071107"
        self.headers = {
            "User-Agent": "amoCRM-API-client/1.0",
            "Content-Type": "application/json",
            "Authorization": f"Bearer {token}"
        }
    # SYSTEM
    @staticmethod
    def read_hook(input_string: str):

        response = dict()

        for element in input_string.split("&"):
            entity = element.split('[')[0]
            if entity not in response.keys():
                response[entity] = dict()
            (name, value) = element.split("=")

            name = name.replace("[", "|").replace("]", "")

            name_array = name.split("|")[1:]
            mypath = ([entity])

            for sub in name_array:
                # print(response[mypath])
                if sub not in response[mypath].keys():
                    response[mypath][sub] = dict()
                    mypath = mypath + ([sub])
                # print(sub, end="\t")

        return response
    # GETTERS

    def get_lead(self, _id, within=""):
        link = f"https://{self.subdomain}.amocrm.ru/api/v4/leads/{_id}?with={within}"
        data_lead = json.loads(req.get(link, headers=self.headers).content.decode())
        return Lead(data_lead, token=self.token)

    def get_contact(self, _id):
        link = f"https://{self.subdomain}.amocrm.ru/api/v4/contacts/{_id}"
        data_contact = json.loads(req.get(link, headers=self.headers).content.decode())
        return Contact(data_contact)

    def get_events(self, params: dict = None):
        filter_amo = ""
        if params:
            for key in params.keys():
                filter_amo = f"{filter_amo}&filter{key}={params[key]}"
        link = f"https://{self.subdomain}.amocrm.ru/api/v4/events?{filter_amo}"
        tasks_array_by_amo = req.get(link, headers=self.headers).json()['_emedded']['events']
        return_array_of_task = []
        for task in tasks_array_by_amo:
            return_array_of_task.append(Task(task))
        else:
            return return_array_of_task

    def get_tasks(self, params: dict = None):
        filter_amo = ""
        if params:
            for key in params.keys():
                filter_amo = f"{filter_amo}&filter{key}={params[key]}"
        link = f"https://{self.subdomain}.amocrm.ru/api/v4/tasks?{filter_amo}"
        tasks_array_by_amo = req.get(link, headers=self.headers).json()['_emedded']['tasks']
        return_array_of_task = []
        for task in tasks_array_by_amo:
            return_array_of_task.append(Task(task))
        else:
            return return_array_of_task

    def search_by_query(self, entity: str, query: str) -> dict:
        link = f"https://{self.subdomain}.amocrm.ru/api/v4/{entity}?query={query}"
        data = req.get(link, headers=self.headers)
        try:
            return data.json()
        except Exception as ex:
            if data.status_code != 200:
                return None
            else:
                return {"data": data.content.decode(), "Error": ex}

    def search_leads_by_filter(self, *args):
        amo_filter = ""
        for element in args:
            if f"{element['key']}={element['value']}" not in amo_filter:
                amo_filter = f"{amo_filter}&filter{element['key']}={element['value']}"
        else:
            amo_filter = amo_filter[1:]
            print(amo_filter)

            response = []
            current_page = 1
            link = f"https://{self.subdomain}.amocrm.ru/api/v4/leads?{amo_filter}&limit=250&page={current_page}&with=source_id"
            data = req.get(link, headers=self.headers).json()
            try:
                while data['_page'] == current_page:
                    for lead in data['_embedded']['leads']:
                        response.append(Lead(lead, token=self.token))
                    else:
                        current_page += 1
                        link = f"https://{self.subdomain}.amocrm.ru/api/v4/leads?{amo_filter}" \
                            f"&limit=250&page={current_page}&with=source_id"
                        sleep(0.5)
                        dater = req.get(link, headers=self.headers)
                        if dater.content == b'':
                            break
                        data = dater.json()
                    if current_page > 500:
                        break
                return response
            except Exception as ex:
                print(f"{ex}")
                return response


    """Change entities"""

    def change_lead(self, data_lead):
        link = f"https://{self.subdomain}.amocrm.ru/api/v4/leads"
        data_lead = [data_lead]
        return req.patch(link, data=json.dumps(data_lead), headers=self.headers).json()

    def change_lead(self, data_lead):
        link = f"https://{self.subdomain}.amocrm.ru/api/v4/leads"
        data_lead = [data_lead]

        return req.patch(link, data=json.dumps(data_lead), headers=self.headers).json()

    def change_lead_package(self, *args):
        link = f"https://{self.subdomain}.amocrm.ru/api/v4/leads"
        data_lead = []
        for lead in args:
            data_lead.append(vars(lead))
        else:
            return req.patch(link, data=json.dumps(data_lead), headers=self.headers).json()

    def change_contact(self, data_contact):
        link = f"https://{self.subdomain}.amocrm.ru/api/v4/contacts"
        data_contact = [data_contact]
        return req.patch(link, data=json.dumps(data_contact), headers=self.headers).json()

    def change_task(self, data_task):
        link = f"https://{self.subdomain}.amocrm.ru/api/v4/tasks"
        data_task = [data_task]
        return req.patch(link, data=json.dumps(data_task), headers=self.headers).json()

    """Create Entities"""
    def create_complex_lead(self, data_lead, data_contact):
        # FIXME Its not compleated
        link = f"https://{self.subdomain}.amocrm.ru/api/v4/leads/complex"
        data = []
        return req.post(link, data=json.dumps(data), headers=self.headers).json()

    def unsorted_send(self, pipeline_id: int, data_lead: dict, data_contact: dict, metadata: dict = dict()) -> dict:
        link = f"https://{self.subdomain}.amocrm.ru/api/v4/leads/unsorted/forms"
        data = [
            {
                "source_name": "Ферма",
                "source_uid": "a1fee7c0fc436088e64ba2e8822ba2b3",
                "pipeline_id": pipeline_id,
                "_embedded": {
                    "leads": [data_lead],
                    "contacts": [data_contact]
                },
                "metadata": {
                    "ip": "0.0.0.0",
                    "form_id": uuqo(32),
                    "form_sent_at": round(time()*1000),
                    "form_name": "Ферма",
                    "form_page": "Ферма",
                    "referer": "https://xn--80ahdy1a6fb.xn--p1ai/"
                }
            }
        ]
        return req.post(link, data=json.dumps(data), headers=self.headers).json()

    def create_lead(self, data_lead):
        return Lead(data_lead, toekn=self.token)

    def create_contact(self, data_contact):
        return Contact(data_contact, token=self.token)

    def create_task(self, amo_id: int, entity_type: str, resp_id: int,
                    text: str, task_type: int, unix_time_comlite: int):
        data_task = {
            "entity_id": amo_id,
            "entity_type": entity_type,
            "responsible_user_id": resp_id,
            "text": text,
            "task_type_id": task_type,
            "complete_till": unix_time_comlite
        }
        return Task(data_task, token=self.token)

    def create_note(self, amo_id: int, text: str):
        # print(amo_id, text)
        data = {
            "entity_id": amo_id,
            "note_type": "common",
            "params": {
                        "text": f"{text}"
                    }
            }

        link = f"https://{self.subdomain}.amocrm.ru/api/v4/leads/notes"
        data = [data]
        return req.post(link, data=json.dumps(data), headers=self.headers).json()
