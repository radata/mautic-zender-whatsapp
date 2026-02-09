<?php

namespace MauticPlugin\ZenderWhatsappBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\FormButtonsType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SendWhatsappType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'contactId',
            HiddenType::class,
            [
                'data' => $options['data']['contactId'] ?? '',
            ]
        );

        $builder->add(
            'messageType',
            ChoiceType::class,
            [
                'label'      => 'zender_wa.send.message_type',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'    => 'form-control',
                    'onchange' => 'ZenderWhatsapp.toggleFields(this.value)',
                ],
                'choices' => [
                    'Text'     => 'text',
                    'Media'    => 'media',
                    'Document' => 'document',
                ],
                'required' => true,
            ]
        );

        $builder->add(
            'message',
            TextareaType::class,
            [
                'label'      => 'zender_wa.send.message',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'       => 'form-control',
                    'rows'        => 4,
                    'placeholder' => 'zender_wa.send.message_placeholder',
                ],
                'required' => true,
            ]
        );

        // Media fields
        $builder->add(
            'mediaUrl',
            TextType::class,
            [
                'label'      => 'zender_wa.send.media_url',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'       => 'form-control',
                    'placeholder' => 'https://example.com/image.jpg',
                ],
                'required' => false,
            ]
        );

        $builder->add(
            'mediaType',
            ChoiceType::class,
            [
                'label'      => 'zender_wa.send.media_type',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
                'choices'    => [
                    'Image' => 'image',
                    'Audio' => 'audio',
                    'Video' => 'video',
                ],
                'required' => false,
            ]
        );

        // Document fields
        $builder->add(
            'documentUrl',
            TextType::class,
            [
                'label'      => 'zender_wa.send.document_url',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'       => 'form-control',
                    'placeholder' => 'https://example.com/document.pdf',
                ],
                'required' => false,
            ]
        );

        $builder->add(
            'documentName',
            TextType::class,
            [
                'label'      => 'zender_wa.send.document_name',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'       => 'form-control',
                    'placeholder' => 'document.pdf',
                ],
                'required' => false,
            ]
        );

        $builder->add(
            'documentType',
            ChoiceType::class,
            [
                'label'      => 'zender_wa.send.document_type',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
                'choices'    => [
                    'PDF'  => 'pdf',
                    'XML'  => 'xml',
                    'XLS'  => 'xls',
                    'XLSX' => 'xlsx',
                    'DOC'  => 'doc',
                    'DOCX' => 'docx',
                ],
                'required' => false,
            ]
        );

        $builder->add(
            'buttons',
            FormButtonsType::class,
            [
                'apply_text'     => false,
                'save_text'      => 'zender_wa.send.submit',
                'cancel_onclick' => 'javascript:void(0);',
                'cancel_attr'    => [
                    'data-dismiss' => 'modal',
                ],
            ]
        );

        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }

    public function getBlockPrefix(): string
    {
        return 'zender_send_whatsapp';
    }
}
