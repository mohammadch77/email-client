<?php

namespace App\Services\Mail;

use App\Exceptions\Mail\SendFailedException;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class SmtpClient
{
    protected Mailer $mailer;

    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
        $dsn = $this->buildDsn($config);
        $transport = Transport::fromDsn($dsn);
        $this->mailer = new Mailer($transport);
    }

    private function buildDsn(array $config): string
    {
        $scheme = match ($config['encryption']) {
            'ssl' => 'smtps',
            'tls' => 'smtp+tls',
            default => 'smtp',
        };

        return sprintf(
            '%s://%s:%s@%s:%d',
            $scheme,
            urlencode($config['username']),
            urlencode($config['password']),
            $config['host'],
            $config['port']
        );
    }

    public function send(array $data): void
    {
        try {
            $email = (new Email)
                ->from(new Address(
                    $data['from_email'],
                    $data['from_name'] ?? ''
                ))
                ->subject($data['subject'] ?? '(No Subject)');

            foreach ($data['to'] as $r) {
                $email->addTo(new Address(
                    $r['email'], $r['name'] ?? ''
                ));
            }

            foreach (($data['cc'] ?? []) as $r) {
                $email->addCc(new Address(
                    $r['email'], $r['name'] ?? ''
                ));
            }

            foreach (($data['bcc'] ?? []) as $r) {
                $email->addBcc(new Address(
                    $r['email'], $r['name'] ?? ''
                ));
            }

            if (! empty($data['body_html'])) {
                $email->html($data['body_html']);
            }
            if (! empty($data['body_text'])) {
                $email->text($data['body_text']);
            }

            if (! empty($data['in_reply_to'])) {
                $email->getHeaders()
                    ->addTextHeader('In-Reply-To', $data['in_reply_to']);
            }
            if (! empty($data['references'])) {
                $email->getHeaders()
                    ->addTextHeader('References', $data['references']);
            }

            $this->mailer->send($email);
        } catch (\Exception $e) {
            throw new SendFailedException(
                'SMTP send failed: '.$e->getMessage()
            );
        }
    }
}
