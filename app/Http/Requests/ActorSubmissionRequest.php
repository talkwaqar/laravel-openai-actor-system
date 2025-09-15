<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\DTOs\ActorSubmissionData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

/**
 * Actor Submission Request
 *
 * Enterprise-level form request with comprehensive validation rules,
 * custom error handling, and business logic validation.
 */
class ActorSubmissionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Allow all submissions for this public endpoint
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'email:rfc',
                'max:255',
                'unique_actor_email',
            ],
            'description' => [
                'required',
                'string',
                'min:10',
                'max:2000',
                'quality_description',
            ],
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Email address is required.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique_actor_email' => 'This email has already been used for an actor submission.',
            'description.required' => 'Actor description is required.',
            'description.min' => 'Description must be at least 10 characters long.',
            'description.max' => 'Description cannot exceed 2000 characters.',
            'description.quality_description' => 'Please provide a more detailed and meaningful description.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'email' => 'email address',
            'description' => 'actor description',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => strtolower(trim($this->email ?? '')),
            'description' => trim($this->description ?? ''),
        ]);
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            // Additional business logic validation
            $this->validateDescriptionContent($validator);
            $this->validateEmailDomain($validator);
        });
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator): void
    {
        if ($this->expectsJson()) {
            throw new HttpResponseException(
                response()->json([
                    'error' => 'validation_failed',
                    'message' => 'The provided data is invalid.',
                    'errors' => $validator->errors()->toArray(),
                ], 422)
            );
        }

        parent::failedValidation($validator);
    }

    /**
     * Get the validated data as a DTO.
     */
    public function toDTO(): ActorSubmissionData
    {
        $validated = $this->validated();

        return ActorSubmissionData::fromRequest([
            'email' => $validated['email'],
            'description' => $validated['description'],
            'ip_address' => $this->ip(),
            'user_agent' => $this->userAgent(),
        ]);
    }

    /**
     * Validate description content quality.
     */
    protected function validateDescriptionContent(Validator $validator): void
    {
        $description = $this->input('description', '');

        // Check for spam patterns
        if ($this->containsSpamPatterns($description)) {
            $validator->errors()->add('description', 'The description appears to contain spam or inappropriate content.');
        }

        // Check for minimum information content
        if (!$this->hasMinimumInformation($description)) {
            $validator->errors()->add('description', 'Please provide more detailed information about the actor (name, physical attributes, etc.).');
        }
    }

    /**
     * Validate email domain.
     */
    protected function validateEmailDomain(Validator $validator): void
    {
        $email = $this->input('email', '');

        if (empty($email)) {
            return;
        }

        $atPosition = strrchr($email, '@');
        if ($atPosition === false) {
            return; // Invalid email format, will be caught by email validation rule
        }

        $domain = substr($atPosition, 1);

        // Block known disposable email domains
        $disposableDomains = [
            '10minutemail.com',
            'tempmail.org',
            'guerrillamail.com',
            'mailinator.com',
        ];

        if (in_array(strtolower($domain), $disposableDomains)) {
            $validator->errors()->add('email', 'Disposable email addresses are not allowed.');
        }
    }

    /**
     * Check if description contains spam patterns.
     */
    protected function containsSpamPatterns(string $description): bool
    {
        $spamPatterns = [
            '/\b(viagra|cialis|casino|lottery|winner|congratulations)\b/i',
            '/\b(click here|visit now|act now|limited time)\b/i',
            '/\$\d+|\d+\$/',
            '/\b\d{10,}\b/', // Long numbers (phone/credit card)
        ];

        foreach ($spamPatterns as $pattern) {
            if (preg_match($pattern, $description)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if description has minimum required information.
     */
    protected function hasMinimumInformation(string $description): bool
    {
        $description = strtolower($description);

        // Look for name indicators
        $nameIndicators = ['name', 'called', 'known as', 'i am', 'my name'];
        $hasNameInfo = false;

        foreach ($nameIndicators as $indicator) {
            if (str_contains($description, $indicator)) {
                $hasNameInfo = true;
                break;
            }
        }

        // Look for physical attribute indicators
        $physicalIndicators = ['tall', 'height', 'weight', 'hair', 'eyes', 'age', 'years old', 'born'];
        $hasPhysicalInfo = false;

        foreach ($physicalIndicators as $indicator) {
            if (str_contains($description, $indicator)) {
                $hasPhysicalInfo = true;
                break;
            }
        }

        // Look for location indicators
        $locationIndicators = ['live', 'from', 'address', 'street', 'city', 'state', 'country'];
        $hasLocationInfo = false;

        foreach ($locationIndicators as $indicator) {
            if (str_contains($description, $indicator)) {
                $hasLocationInfo = true;
                break;
            }
        }

        // Require at least 2 out of 3 categories
        $infoCount = (int)$hasNameInfo + (int)$hasPhysicalInfo + (int)$hasLocationInfo;

        return $infoCount >= 2;
    }
}
