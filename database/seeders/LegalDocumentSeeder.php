<?php
namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LegalDocumentSeeder extends Seeder
{
    /**
     * Seed the legal documents master data.
     *
     * This seeder is idempotent and can safely be executed multiple times.
     */
    public function run(): void
    {
        $effectiveAt = Carbon::now();

        $documents = [
            [
                'document_type' => 'TERMS',
                'version'       => '1.0',
                'title'         => 'Terms & Conditions',
                'content'       => <<<'HTML'
<h2>Terms & Conditions</h2>

<p>Welcome to CMES. These Terms & Conditions govern your access to and use of the CMES application, website, services, and related features.</p>

<h3>1. Acceptance of Terms</h3>
<p>By accessing or using CMES, you acknowledge that you have read, understood, and agree to be bound by these Terms & Conditions and applicable laws and regulations.</p>

<h3>2. User Account</h3>
<p>Users are responsible for providing accurate information during registration and for maintaining the confidentiality of their account credentials and authentication information.</p>

<h3>3. Use of the Platform</h3>
<p>CMES shall be used only for lawful purposes and in accordance with applicable mining, environmental, regulatory, administrative, and other applicable requirements.</p>

<h3>4. Information and Documents</h3>
<p>Users are responsible for ensuring that information, applications, documents, declarations, and other records submitted through CMES are accurate, complete, current, and authentic.</p>

<h3>5. Intellectual Property</h3>
<p>Unless otherwise stated, the software, design, content, trademarks, interfaces, and other materials made available through CMES are protected by applicable intellectual property laws.</p>

<h3>6. Availability of Services</h3>
<p>Reasonable efforts may be made to maintain the availability of CMES. However, temporary interruption may occur due to maintenance, technical issues, network failures, security incidents, or circumstances beyond reasonable control.</p>

<h3>7. User Responsibilities</h3>
<p>Users shall not misuse the platform, attempt unauthorized access, introduce malicious software, interfere with system operations, or use the platform for any unlawful activity.</p>

<h3>8. Changes to These Terms</h3>
<p>These Terms & Conditions may be updated from time to time. The updated version will be published through the platform along with its applicable version and effective date.</p>

<h3>9. Governing Law</h3>
<p>These Terms shall be governed by and interpreted in accordance with the applicable laws of India.</p>

<h3>10. Contact</h3>
<p>For questions regarding these Terms & Conditions, users may contact the designated CMES support or administrative authority.</p>
HTML,
                'status'        => 'PUBLISHED',
                'effective_at'  => $effectiveAt,
            ],

            [
                'document_type' => 'PRIVACY',
                'version'       => '1.0',
                'title'         => 'Privacy Policy',
                'content'       => <<<'HTML'
<h2>Privacy Policy</h2>

<p>This Privacy Policy describes how CMES may collect, use, store, process, and protect information provided by users while accessing and using the CMES application and related services.</p>

<h3>1. Information We May Collect</h3>
<p>Depending on the services used, CMES may collect information such as name, contact details, account information, project-related information, documents, application data, and technical information required to provide the services.</p>

<h3>2. Use of Information</h3>
<p>Information may be used for account management, project administration, document processing, notifications, compliance-related workflows, support services, security, reporting, and improvement of the platform.</p>

<h3>3. Document and Project Information</h3>
<p>Information and documents submitted through CMES may be processed for the specific administrative, operational, regulatory, or service-related purposes for which they were submitted.</p>

<h3>4. Information Security</h3>
<p>Reasonable technical and organizational measures may be implemented to protect information against unauthorized access, alteration, disclosure, loss, or misuse.</p>

<h3>5. Data Sharing</h3>
<p>Information may be disclosed to authorized personnel, government authorities, service providers, or other entities where required for legitimate operational purposes or where required or permitted by applicable law.</p>

<h3>6. Data Retention</h3>
<p>Information may be retained for as long as necessary to provide services, meet operational requirements, comply with applicable laws, resolve disputes, and maintain appropriate records.</p>

<h3>7. User Responsibilities</h3>
<p>Users should ensure that the information submitted through CMES is accurate and should protect their account credentials and authentication information.</p>

<h3>8. Policy Updates</h3>
<p>This Privacy Policy may be updated periodically. The latest published version and effective date will be displayed through the CMES platform.</p>

<h3>9. Contact</h3>
<p>For privacy-related questions or requests, users may contact the designated CMES support or administrative authority.</p>
HTML,
                'status'        => 'PUBLISHED',
                'effective_at'  => $effectiveAt,
            ],

            [
                'document_type' => 'DISCLAIMER',
                'version'       => '1.0',
                'title'         => 'Disclaimer',
                'content'       => <<<'HTML'
<h2>Disclaimer</h2>

<p>The information and services made available through CMES are provided for the purposes supported by the platform and should be used in accordance with applicable laws, rules, regulations, notifications, and official directions.</p>

<h3>1. Information Accuracy</h3>
<p>Reasonable efforts may be made to maintain accurate and current information. However, CMES does not guarantee that all information available through the platform will always be complete, accurate, current, or free from errors.</p>

<h3>2. Official Records</h3>
<p>Where information displayed through CMES is derived from official records or external systems, the relevant official record or competent authority shall prevail in case of any discrepancy.</p>

<h3>3. Regulatory Decisions</h3>
<p>Information displayed through the platform does not by itself constitute approval, authorization, certification, legal advice, or a regulatory decision unless expressly issued by the competent authority through an authorized process.</p>

<h3>4. Third-Party Services</h3>
<p>CMES may integrate with or reference third-party systems or services. Availability and accuracy of information supplied by such systems may be subject to the respective third-party service.</p>

<h3>5. Technical Availability</h3>
<p>Temporary interruptions may occur due to maintenance, network issues, system failures, security incidents, or circumstances beyond reasonable control.</p>

<h3>6. User Responsibility</h3>
<p>Users are responsible for verifying important information and complying with applicable laws, regulations, procedures, and official instructions before taking any action based on information available through CMES.</p>

<h3>7. Changes</h3>
<p>This Disclaimer may be updated periodically. The latest published version shall be considered applicable from its stated effective date.</p>

<h3>8. Contact</h3>
<p>For questions regarding this Disclaimer, users may contact the designated CMES support or administrative authority.</p>
HTML,
                'status'        => 'PUBLISHED',
                'effective_at'  => $effectiveAt,
            ],
        ];

        foreach ($documents as $document) {
            DB::table('legal_documents')->updateOrInsert(
                [
                    'document_type' => $document['document_type'],
                    'version'       => $document['version'],
                ],
                [
                    'title'        => $document['title'],
                    'content'      => $document['content'],
                    'status'       => $document['status'],
                    'effective_at' => $document['effective_at'],
                    'updated_at'   => $effectiveAt,
                ]
            );
        }
    }
}
