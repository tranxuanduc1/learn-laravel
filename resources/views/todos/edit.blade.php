@extends('layouts.app')

@section('content')
    <main>
        <p><a href="{{ route('todos.index') }}">&larr; Back to todos</a></p>

        <h1>Edit todo</h1>

        <form id="todo-edit-form" action="{{ route('todos.update', $todo) }}" method="POST">
            @csrf
            @method('PATCH')

            <div>
                <label for="title">Title</label>
                <input
                    id="title"
                    name="title"
                    type="text"
                    maxlength="255"
                    value="{{ $todo->title }}"
                    required
                >
                <p id="title-error" role="alert" hidden></p>
            </div>

            <div>
                <label>
                    <input
                        id="completed"
                        name="completed"
                        type="checkbox"
                        value="1"
                        @checked($todo->completed)
                    >
                    Completed
                </label>
                <p id="completed-error" role="alert" hidden></p>
            </div>

            <button id="save-button" type="submit">Save</button>
            <p id="save-status" role="status" aria-live="polite"></p>
        </form>
    </main>

    <script>
        const form = document.getElementById('todo-edit-form');
        const saveButton = document.getElementById('save-button');
        const saveStatus = document.getElementById('save-status');
        const titleError = document.getElementById('title-error');
        const completedError = document.getElementById('completed-error');
        const loginUrl = @json(route('login'));

        function messageForStatus(status) {
            if (status === 401) {
                return 'Your session has ended. Redirecting to login...';
            }

            if (status === 403 || status === 404) {
                return 'This todo is unavailable or you do not have permission to update it.';
            }

            if (status === 419) {
                return 'Your page session has expired. Refresh the page and try again.';
            }

            if (status === 500) {
                return 'The server encountered an unexpected error. Please try again.';
            }

            return 'Unable to save the todo. Please try again.';
        }

        function clearErrors() {
            for (const element of [titleError, completedError]) {
                element.textContent = '';
                element.hidden = true;
            }
        }

        function showValidationErrors(errors) {
            for (const [field, messages] of Object.entries(errors)) {
                const element = document.getElementById(`${field}-error`);

                if (element) {
                    element.textContent = messages[0];
                    element.hidden = false;
                }
            }
        }

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            clearErrors();
            saveStatus.textContent = 'Saving...';
            saveButton.disabled = true;

            try {
                const response = await fetch(form.action, {
                    method: 'PATCH',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': form.querySelector('[name="_token"]').value,
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        title: document.getElementById('title').value,
                        completed: document.getElementById('completed').checked,
                    }),
                });

                if (response.status === 401) {
                    saveStatus.textContent = messageForStatus(response.status);
                    window.location.assign(loginUrl);
                    return;
                }

                if (response.status === 422) {
                    const payload = await response.json();
                    showValidationErrors(payload.errors ?? {});
                    saveStatus.textContent = 'Please correct the highlighted fields.';
                    return;
                }

                if (!response.ok) {
                    saveStatus.textContent = messageForStatus(response.status);
                    return;
                }

                const payload = await response.json();
                document.getElementById('title').value = payload.data.title;
                document.getElementById('completed').checked = payload.data.completed;
                saveStatus.textContent = 'Todo saved successfully.';
            } catch (error) {
                saveStatus.textContent = 'Unable to save the todo. Check your connection and try again.';
            } finally {
                saveButton.disabled = false;
            }
        });
    </script>
@endsection
